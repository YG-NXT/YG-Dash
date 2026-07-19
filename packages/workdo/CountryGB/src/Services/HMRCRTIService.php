<?php

namespace Workdo\CountryGB\Services;

use App\Classes\GovernmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HMRCRTIService implements GovernmentService
{
    private string $baseUrl = 'https://api.service.hmrc.gov.uk';
    private ?string $accessToken = null;

    public function getName(): string
    {
        return 'HMRC RTI';
    }

    public function getType(): string
    {
        return 'payroll';
    }

    public function getCountryCodes(): array
    {
        return ['GB'];
    }

    public function authenticate(array $credentials): mixed
    {
        $clientId = $credentials['client_id'] ?? '';
        $clientSecret = $credentials['client_secret'] ?? '';
        $redirectUri = $credentials['redirect_uri'] ?? '';
        $authorizationCode = $credentials['authorization_code'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            return ['success' => false, 'error' => 'Missing HMRC credentials'];
        }

        if (!empty($authorizationCode)) {
            $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
                'code' => $authorizationCode,
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');

                return [
                    'success' => true,
                    'access_token' => $this->accessToken,
                    'refresh_token' => $response->json('refresh_token'),
                    'expires_in' => $response->json('expires_in'),
                ];
            }

            return ['success' => false, 'error' => 'HMRC RTI authentication failed'];
        }

        return ['success' => false, 'error' => 'Missing authorization code'];
    }

    public function submit(string $endpoint, array $data): mixed
    {
        $empref = $data['empref'] ?? '';
        if (empty($empref)) {
            return ['success' => false, 'error' => 'Employer reference (EMPref) is required'];
        }

        $url = $this->baseUrl . '/payroll/employers/' . rawurlencode($empref) . $endpoint;

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.1.0+json')
            ->post($url, $data['payload'] ?? []);

        if ($response->successful() || $response->status() === 201) {
            return [
                'success' => true,
                'reference' => $response->json('paymentId') ?: ($response->header('Location') ? basename($response->header('Location')) : null),
                'status' => $response->json('state') ?? 'submitted',
                'raw' => $response->json(),
            ];
        }

        $errorBody = $response->json();
        $errorMessage = $errorBody['message'] ?? $errorBody['error'] ?? 'Unknown error';

        Log::error('HMRC RTI submission failed', [
            'empref' => $empref,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'error' => $errorBody,
        ]);

        return [
            'success' => false,
            'error' => $errorMessage,
            'details' => $errorBody,
            'status' => $response->status(),
        ];
    }

    public function getStatus(string $documentId): array
    {
        return ['status' => 'submitted', 'document_id' => $documentId];
    }

    public function submitFPS(string $empref, array $payrollData): mixed
    {
        return $this->submit('/fps', [
            'empref' => $empref,
            'payload' => $payrollData,
        ]);
    }

    public function submitEAS(string $empref, array $alignmentData): mixed
    {
        return $this->submit('/eas', [
            'empref' => $empref,
            'payload' => $alignmentData,
        ]);
    }

    public function getEmployerPAYEDetails(string $empref, string $taxYear): mixed
    {
        $url = $this->baseUrl . '/payroll/employers/' . rawurlencode($empref) . '/employment/allowances/' . rawurlencode($taxYear);

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.1.0+json')
            ->get($url);

        if ($response->successful()) {
            return [
                'success' => true,
                'allowances' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to fetch PAYE details',
        ];
    }

    public function getAuthUrl(string $clientId, string $redirectUri, array $scopes = []): string
    {
        $defaultScopes = [
            'read:payroll',
            'write:payroll',
        ];

        $allScopes = array_unique(array_merge($defaultScopes, $scopes));

        return $this->baseUrl . '/oauth/authorize?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $allScopes),
        ]);
    }
}
