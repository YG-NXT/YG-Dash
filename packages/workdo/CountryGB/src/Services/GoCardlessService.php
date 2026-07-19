<?php

namespace Workdo\CountryGB\Services;

use App\Classes\GovernmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoCardlessService implements GovernmentService
{
    private string $apiKey = '';
    private string $baseUrl = 'https://api.gocardless.com';

    public function getName(): string
    {
        return 'GoCardless';
    }

    public function getType(): string
    {
        return 'payment_gateway';
    }

    public function getCountryCodes(): array
    {
        return ['GB'];
    }

    public function authenticate(array $credentials): mixed
    {
        $this->apiKey = $credentials['api_key'] ?? '';

        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'GoCardless API key is required'];
        }

        return [
            'success' => true,
            'api_key' => $this->apiKey,
        ];
    }

    public function submit(string $endpoint, array $data): mixed
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'GoCardless not configured'];
        }

        $url = $this->baseUrl . $endpoint;

        $response = Http::withToken($this->apiKey)
            ->accept('application/json')
            ->post($url, $data['payload'] ?? []);

        if ($response->successful() || $response->status() === 201) {
            return [
                'success' => true,
                'reference' => $response->json('id') ?? $response->header('Location'),
                'raw' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error')['message'] ?? 'GoCardless request failed',
            'details' => $response->json(),
        ];
    }

    public function getStatus(string $documentId): array
    {
        if (empty($this->apiKey)) {
            return ['status' => 'error', 'error' => 'GoCardless not configured'];
        }

        $response = Http::withToken($this->apiKey)
            ->accept('application/json')
            ->get($this->baseUrl . '/payments/' . $documentId);

        if ($response->successful()) {
            $payment = $response->json('payments') ?? $response->json();

            return [
                'status' => $payment['status'] ?? 'unknown',
                'amount' => $payment['amount'] ?? 0,
                'currency' => $payment['currency'] ?? 'GBP',
                'raw' => $payment,
            ];
        }

        return ['status' => 'error', 'error' => $response->json('error')['message'] ?? 'Unknown error'];
    }

    public function createMandate(string $customerId, string $scheme = 'bacs'): mixed
    {
        return $this->submit('/customer_bank_accounts', [
            'payload' => [
                'customer_id' => $customerId,
                'scheme' => $scheme,
            ],
        ]);
    }

    public function createPayment(string $mandateId, int $amount, string $currency = 'GBP', string $description = ''): mixed
    {
        return $this->submit('/payments', [
            'payload' => [
                'amount' => $amount,
                'currency' => strtolower($currency),
                'mandate_id' => $mandateId,
                'description' => $description,
            ],
        ]);
    }

    public function getSupportedPaymentMethods(): array
    {
        return [
            'bacs_debit',
            'instant_bank_pay',
        ];
    }
}
