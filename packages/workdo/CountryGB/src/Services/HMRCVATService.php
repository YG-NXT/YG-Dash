<?php

namespace Workdo\CountryGB\Services;

use App\Classes\GovernmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HMRCVATService implements GovernmentService
{
    private string $baseUrl = 'https://api.service.hmrc.gov.uk';
    private string $tokenEndpoint = '/oauth/token';
    private ?string $accessToken = null;

    public function getName(): string
    {
        return 'HMRC VAT';
    }

    public function getType(): string
    {
        return 'tax';
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
            $response = Http::asForm()->post($this->baseUrl . $this->tokenEndpoint, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
                'code' => $authorizationCode,
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                $refreshToken = $response->json('refresh_token');
                $expiresIn = $response->json('expires_in');

                return [
                    'success' => true,
                    'access_token' => $this->accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_in' => $expiresIn,
                    'token_type' => $response->json('token_type'),
                ];
            }

            Log::error('HMRC OAuth2 token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'error' => 'HMRC authentication failed', 'details' => $response->body()];
        }

        return ['success' => false, 'error' => 'Missing authorization code'];
    }

    public function refreshToken(string $refreshToken, array $credentials): mixed
    {
        $clientId = $credentials['client_id'] ?? '';
        $clientSecret = $credentials['client_secret'] ?? '';

        $response = Http::asForm()->post($this->baseUrl . $this->tokenEndpoint, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
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

        return ['success' => false, 'error' => 'Token refresh failed'];
    }

    public function submit(string $endpoint, array $data): mixed
    {
        $vrn = $data['vrn'] ?? '';
        if (empty($vrn)) {
            return ['success' => false, 'error' => 'VRN is required'];
        }

        $url = $this->baseUrl . '/organisations/vat/' . rawurlencode($vrn) . $endpoint;

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.v1+json')
            ->post($url, $data['payload'] ?? []);

        if ($response->successful() || $response->status() === 201) {
            $location = $response->header('Location');

            return [
                'success' => true,
                'reference' => $response->json('processingDate') ?: ($location ? basename($location) : null),
                'status' => $response->json('state') ?? 'submitted',
                'raw' => $response->json(),
            ];
        }

        $errorBody = $response->json();
        $errorMessage = $errorBody['message'] ?? $errorBody['error'] ?? 'Unknown error';

        Log::error('HMRC VAT submission failed', [
            'vrn' => $vrn,
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

    public function submitVATReturn(string $vrn, array $vatReturn): mixed
    {
        return $this->submit('/returns', [
            'vrn' => $vrn,
            'payload' => $vatReturn,
        ]);
    }

    public function getObligations(string $vrn, string $from = null, string $to = null, string $status = 'O'): mixed
    {
        $url = $this->baseUrl . '/organisations/vat/' . rawurlencode($vrn) . '/obligations';

        $params = ['status' => $status];
        if ($from) $params['from'] = $from;
        if ($to) $params['to'] = $to;

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.v1+json')
            ->get($url, $params);

        if ($response->successful()) {
            return [
                'success' => true,
                'obligations' => $response->json('obligations') ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to fetch obligations',
        ];
    }

    public function getLiabilities(string $vrn, string $from = null, string $to = null): mixed
    {
        $url = $this->baseUrl . '/organisations/vat/' . rawurlencode($vrn) . '/liabilities';

        $params = [];
        if ($from) $params['from'] = $from;
        if ($to) $params['to'] = $to;

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.v1+json')
            ->get($url, $params);

        if ($response->successful()) {
            return [
                'success' => true,
                'liabilities' => $response->json('liabilities') ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to fetch liabilities',
        ];
    }

    public function getPayments(string $vrn, string $from = null, string $to = null): mixed
    {
        $url = $this->baseUrl . '/organisations/vat/' . rawurlencode($vrn) . '/payments';

        $params = [];
        if ($from) $params['from'] = $from;
        if ($to) $params['to'] = $to;

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.v1+json')
            ->get($url, $params);

        if ($response->successful()) {
            return [
                'success' => true,
                'payments' => $response->json('payments') ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to fetch payments',
        ];
    }

    public function getVATReturn(string $vrn, string $periodKey): mixed
    {
        $url = $this->baseUrl . '/organisations/vat/' . rawurlencode($vrn) . '/returns/' . rawurlencode($periodKey);

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.v1+json')
            ->get($url);

        if ($response->successful()) {
            return [
                'success' => true,
                'return' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to fetch VAT return',
        ];
    }

    public function getAuthorizedClients(string $clientId): mixed
    {
        $url = $this->baseUrl . '/test/organisations/agent/shortcuts/';

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.v1+json')
            ->get($url);

        if ($response->successful()) {
            return [
                'success' => true,
                'clients' => $response->json() ?? [],
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to fetch authorized clients',
        ];
    }

    public function validateVATNumber(string $vatNumber): mixed
    {
        $url = $this->baseUrl . '/vat/checker/vat-number';

        $response = Http::withToken($this->accessToken)
            ->accept('application/vnd.hmrc.v1+json')
            ->post($url, [
                'vatNumber' => $vatNumber,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'valid' => $response->json('valid') ?? false,
                'address' => $response->json('address') ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'VAT validation failed',
        ];
    }

    public function getAuthUrl(string $clientId, string $redirectUri, array $scopes = []): string
    {
        $defaultScopes = [
            'read:vat',
            'write:vat',
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
