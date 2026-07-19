<?php

namespace Workdo\CountryGB\Services;

use App\Classes\GovernmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FSAService implements GovernmentService
{
    private string $baseUrl = 'https://ratings.food.gov.uk/open-data';

    public function getName(): string
    {
        return 'Food Standards Agency';
    }

    public function getType(): string
    {
        return 'food_safety';
    }

    public function getCountryCodes(): array
    {
        return ['GB'];
    }

    public function authenticate(array $credentials): mixed
    {
        return [
            'success' => true,
            'note' => 'FSA Open Data does not require authentication',
        ];
    }

    public function submit(string $endpoint, array $data): mixed
    {
        return [
            'success' => false,
            'error' => 'FSA Open Data is read-only. Inspection submission is handled via local authority.',
        ];
    }

    public function getStatus(string $documentId): array
    {
        return ['status' => 'read_only', 'document_id' => $documentId];
    }

    public function searchEstablishments(string $name = null, string $address = null, string $localAuthority = null, int $page = 1): mixed
    {
        $params = [
            'page' => $page,
            'pageSize' => 50,
        ];

        if ($name) $params['name'] = $name;
        if ($address) $params['address'] = $address;
        if ($localAuthority) $params['localAuthority'] = $localAuthority;

        $response = Http::accept('application/json')
            ->get($this->baseUrl . '/en-GB/establishments', $params);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'establishments' => $data['establishments'] ?? [],
                'total_results' => $data['meta']['totalCount'] ?? 0,
                'page' => $data['meta']['page'] ?? $page,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Search failed',
        ];
    }

    public function getEstablishmentByFHRSID(int $fhrsId): mixed
    {
        $response = Http::accept('application/json')
            ->get($this->baseUrl . '/en-GB/establishments/' . $fhrsId);

        if ($response->successful()) {
            $establishment = $response->json('establishment');

            return [
                'success' => true,
                'establishment' => $establishment,
                'rating' => [
                    'value' => $establishment['RatingValue'] ?? null,
                    'date' => $establishment['RatingDate'] ?? null,
                    'authority' => $establishment['LocalAuthorityName'] ?? null,
                ],
            ];
        }

        if ($response->status() === 404) {
            return [
                'success' => false,
                'error' => 'Establishment not found',
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch establishment',
        ];
    }

    public function getRatingByBusiness(string $businessName, string $postcode = null): mixed
    {
        $params = ['name' => $businessName];
        if ($postcode) $params['address'] = $postcode;

        $result = $this->searchEstablishments(
            $businessName,
            $postcode
        );

        if ($result['success'] && !empty($result['establishments'])) {
            $establishment = $result['establishments'][0];

            return [
                'success' => true,
                'rating' => [
                    'value' => $establishment['RatingValue'] ?? null,
                    'date' => $establishment['RatingDate'] ?? null,
                    'authority' => $establishment['LocalAuthorityName'] ?? null,
                    'business_name' => $establishment['BusinessName'] ?? $businessName,
                    'address' => $establishment['AddressLine1'] ?? '',
                ],
            ];
        }

        return [
            'success' => false,
            'error' => 'No hygiene rating found for this business',
        ];
    }

    public function getLocalAuthorities(): mixed
    {
        $response = Http::accept('application/json')
            ->get($this->baseUrl . '/en-GB/local-authorities');

        if ($response->successful()) {
            return [
                'success' => true,
                'authorities' => $response->json('localAuthorities') ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch local authorities',
        ];
    }

    public function validateFHRSID(int $fhrsId): mixed
    {
        $result = $this->getEstablishmentByFHRSID($fhrsId);

        if ($result['success']) {
            return [
                'valid' => true,
                'business_name' => $result['establishment']['BusinessName'] ?? null,
                'rating' => $result['establishment']['RatingValue'] ?? null,
                'address' => $result['establishment']['AddressLine1'] ?? '',
            ];
        }

        return [
            'valid' => false,
            'error' => $result['error'] ?? 'Invalid FHRS ID',
        ];
    }
}
