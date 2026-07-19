<?php

namespace Workdo\CountryGB\Services;

use App\Classes\GovernmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompaniesHouseService implements GovernmentService
{
    private string $baseUrl = 'https://api.company-information.service.gov.uk';

    public function getName(): string
    {
        return 'Companies House';
    }

    public function getType(): string
    {
        return 'business_registry';
    }

    public function getCountryCodes(): array
    {
        return ['GB'];
    }

    public function authenticate(array $credentials): mixed
    {
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Companies House API key is required'];
        }

        return [
            'success' => true,
            'api_key' => $apiKey,
        ];
    }

    public function submit(string $endpoint, array $data): mixed
    {
        $apiKey = $data['api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API key required'];
        }

        $url = $this->baseUrl . $endpoint;

        $response = Http::withBasicAuth($apiKey, '')
            ->accept('application/json')
            ->post($url, $data['payload'] ?? []);

        if ($response->successful() || $response->status() === 201) {
            return [
                'success' => true,
                'reference' => $response->header('Location') ? basename($response->header('Location')) : null,
                'raw' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Submission failed',
            'details' => $response->json(),
        ];
    }

    public function getStatus(string $documentId): array
    {
        return ['status' => 'filed', 'document_id' => $documentId];
    }

    public function searchCompanies(string $query, int $page = 1, int $itemsPerPage = 20): mixed
    {
        $apiKey = config('services.companies_house.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Companies House API key not configured'];
        }

        $response = Http::withBasicAuth($apiKey, '')
            ->accept('application/json')
            ->get($this->baseUrl . '/search/companies', [
                'q' => $query,
                'page' => $page,
                'items_per_page' => $itemsPerPage,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'companies' => $response->json('items') ?? [],
                'total_results' => $response->json('total_results') ?? 0,
                'page' => $response->json('page_number') ?? $page,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Search failed',
        ];
    }

    public function getCompanyByNumber(string $companyNumber): mixed
    {
        $apiKey = config('services.companies_house.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Companies House API key not configured'];
        }

        $response = Http::withBasicAuth($apiKey, '')
            ->accept('application/json')
            ->get($this->baseUrl . '/company/' . rawurlencode($companyNumber));

        if ($response->successful()) {
            return [
                'success' => true,
                'company' => $response->json(),
            ];
        }

        if ($response->status() === 404) {
            return [
                'success' => false,
                'error' => 'Company not found',
                'company_number' => $companyNumber,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch company',
        ];
    }

    public function getOfficers(string $companyNumber): mixed
    {
        $apiKey = config('services.companies_house.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Companies House API key not configured'];
        }

        $response = Http::withBasicAuth($apiKey, '')
            ->accept('application/json')
            ->get($this->baseUrl . '/company/' . rawurlencode($companyNumber) . '/officers');

        if ($response->successful()) {
            return [
                'success' => true,
                'officers' => $response->json('items') ?? [],
                'total_results' => $response->json('total_results') ?? 0,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch officers',
        ];
    }

    public function getFilingHistory(string $companyNumber, int $page = 1): mixed
    {
        $apiKey = config('services.companies_house.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Companies House API key not configured'];
        }

        $response = Http::withBasicAuth($apiKey, '')
            ->accept('application/json')
            ->get($this->baseUrl . '/company/' . rawurlencode($companyNumber) . '/filing-history', [
                'page' => $page,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'filings' => $response->json('items') ?? [],
                'total_results' => $response->json('total_results') ?? 0,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch filing history',
        ];
    }

    public function getCompanyProfile(string $companyNumber): mixed
    {
        return $this->getCompanyByNumber($companyNumber);
    }

    public function validateCompanyNumber(string $companyNumber): mixed
    {
        $result = $this->getCompanyByNumber($companyNumber);

        if ($result['success']) {
            return [
                'valid' => true,
                'company_name' => $result['company']['company_name'] ?? null,
                'status' => $result['company']['company_status'] ?? null,
                'type' => $result['company']['type'] ?? null,
            ];
        }

        return [
            'valid' => false,
            'error' => $result['error'] ?? 'Invalid company number',
        ];
    }
}
