<?php

namespace Workdo\CountryGB\Services;

use App\Classes\GovernmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CQCService implements GovernmentService
{
    private string $baseUrl = 'https://api.cqc.org.uk/public/v1';

    public function getName(): string
    {
        return 'Care Quality Commission';
    }

    public function getType(): string
    {
        return 'healthcare_regulator';
    }

    public function getCountryCodes(): array
    {
        return ['GB'];
    }

    public function authenticate(array $credentials): mixed
    {
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'CQC API key is required'];
        }

        return [
            'success' => true,
            'api_key' => $apiKey,
        ];
    }

    public function submit(string $endpoint, array $data): mixed
    {
        $apiKey = config('services.cqc.api_key') ?? $data['api_key'] ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'CQC API key not configured'];
        }

        $url = $this->baseUrl . $endpoint;

        $response = Http::withToken($apiKey)
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
        return ['status' => 'registered', 'document_id' => $documentId];
    }

    public function getProviderByLocationId(string $locationId): mixed
    {
        $apiKey = config('services.cqc.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'CQC API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/providers/' . rawurlencode($locationId));

        if ($response->successful()) {
            return [
                'success' => true,
                'provider' => $response->json(),
            ];
        }

        if ($response->status() === 404) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch provider',
        ];
    }

    public function getProviderByName(string $name): mixed
    {
        $apiKey = config('services.cqc.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'CQC API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/providers/search', [
                'name' => $name,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'providers' => $response->json() ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Search failed',
        ];
    }

    public function getInspectionReports(string $providerId): mixed
    {
        $apiKey = config('services.cqc.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'CQC API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/providers/' . rawurlencode($providerId) . '/reports');

        if ($response->successful()) {
            return [
                'success' => true,
                'reports' => $response->json() ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch inspection reports',
        ];
    }

    public function getRating(string $providerId): mixed
    {
        $apiKey = config('services.cqc.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'CQC API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/providers/' . rawurlencode($providerId) . '/ratings');

        if ($response->successful()) {
            return [
                'success' => true,
                'rating' => $response->json('currentRating') ?? $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch rating',
        ];
    }

    public function validateCQCRegistration(string $locationId): mixed
    {
        $result = $this->getProviderByLocationId($locationId);

        if ($result['success']) {
            return [
                'valid' => true,
                'provider_name' => $result['provider']['providerName'] ?? null,
                'rating' => $result['provider']['currentRating'] ?? null,
                'status' => $result['provider']['registrationStatus'] ?? null,
            ];
        }

        return [
            'valid' => false,
            'error' => $result['error'] ?? 'Invalid CQC registration',
        ];
    }
}
