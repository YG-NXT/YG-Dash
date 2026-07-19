<?php

namespace Workdo\CountryGB\Services;

use App\Classes\GovernmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NHSSpineService implements GovernmentService
{
    private string $baseUrl = 'https://api.nhs.uk';

    public function getName(): string
    {
        return 'NHS Spine';
    }

    public function getType(): string
    {
        return 'healthcare_registry';
    }

    public function getCountryCodes(): array
    {
        return ['GB'];
    }

    public function authenticate(array $credentials): mixed
    {
        $apiKey = $credentials['api_key'] ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'NHS API key is required'];
        }

        return [
            'success' => true,
            'api_key' => $apiKey,
        ];
    }

    public function submit(string $endpoint, array $data): mixed
    {
        $apiKey = config('services.nhs.api_key') ?? $data['api_key'] ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'NHS API key not configured'];
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
            'error' => $response->json('error') ?? $response->json('message') ?? 'Submission failed',
            'details' => $response->json(),
            'status' => $response->status(),
        ];
    }

    public function getStatus(string $documentId): array
    {
        return ['status' => 'processed', 'document_id' => $documentId];
    }

    public function validateNHSNumber(string $nhsNumber): mixed
    {
        $nhsNumber = preg_replace('/\s+/', '', $nhsNumber);

        if (!preg_match('/^\d{10}$/', $nhsNumber)) {
            return [
                'valid' => false,
                'error' => 'NHS number must be 10 digits',
            ];
        }

        $checkDigit = (int) substr($nhsNumber, -1);
        $digits = str_split(substr($nhsNumber, 0, 9));

        $total = 0;
        foreach ($digits as $index => $digit) {
            $total += (int) $digit * (10 - $index);
        }

        $expectedCheck = (11 - ($total % 11)) % 11;

        if ($expectedCheck === 10) {
            $expectedCheck = 0;
        }

        $valid = $expectedCheck === $checkDigit;

        return [
            'valid' => $valid,
            'nhs_number' => $valid ? $nhsNumber : null,
            'error' => $valid ? null : 'Invalid NHS number checksum',
        ];
    }

    public function getPatientDemographics(string $nhsNumber): mixed
    {
        $apiKey = config('services.nhs.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'NHS API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/demographics/demographics', [
                'nhsNumber' => preg_replace('/\s+/', '', $nhsNumber),
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'patient' => $response->json('demographics') ?? $response->json(),
            ];
        }

        if ($response->status() === 404) {
            return [
                'success' => false,
                'error' => 'Patient not found on NHS Spine',
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch patient demographics',
        ];
    }

    public function getGPPractice(string $odsCode): mixed
    {
        $apiKey = config('services.nhs.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'NHS API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/directory/api/organisations/' . rawurlencode($odsCode));

        if ($response->successful()) {
            return [
                'success' => true,
                'practice' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch GP practice',
        ];
    }

    public function searchGPPractices(string $query, string $location = null): mixed
    {
        $apiKey = config('services.nhs.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'NHS API key not configured'];
        }

        $params = ['name' => $query];
        if ($location) $params['location'] = $location;

        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/directory/api/organisations', $params);

        if ($response->successful()) {
            return [
                'success' => true,
                'practices' => $response->json('organisations') ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Search failed',
        ];
    }

    public function submitReferral(array $referralData): mixed
    {
        return $this->submit('/referral/referral', [
            'api_key' => config('services.nhs.api_key'),
            'payload' => $referralData,
        ]);
    }

    public function getAppointmentSlots(string $odsCode, string $from, string $to): mixed
    {
        $apiKey = config('services.nhs.api_key') ?? '';

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'NHS API key not configured'];
        }

        $response = Http::withToken($apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/booking/clinical-sessions', [
                'odsCode' => $odsCode,
                'start' => $from,
                'end' => $to,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'slots' => $response->json() ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to fetch appointment slots',
        ];
    }
}
