<?php

namespace Workdo\CountryGB\Services;

use App\Classes\GovernmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeUKService implements GovernmentService
{
    private string $apiKey = '';
    private string $webhookSecret = '';

    public function getName(): string
    {
        return 'Stripe UK';
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
        $this->webhookSecret = $credentials['webhook_secret'] ?? '';

        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Stripe API key is required'];
        }

        return [
            'success' => true,
            'api_key' => $this->apiKey,
            'webhook_secret' => $this->webhookSecret,
        ];
    }

    public function submit(string $endpoint, array $data): mixed
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Stripe not configured'];
        }

        $url = 'https://api.stripe.com/v1' . $endpoint;

        $response = Http::withToken($this->apiKey)
            ->asMultipart()
            ->post($url, $this->formatStripeData($data['payload'] ?? []));

        if ($response->successful() || $response->status() === 200) {
            return [
                'success' => true,
                'reference' => $response->json('id'),
                'raw' => $response->json(),
            ];
        }

        $error = $response->json('error') ?? [];
        $errorMessage = $error['message'] ?? 'Stripe request failed';

        Log::error('Stripe UK request failed', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'error' => $error,
        ]);

        return [
            'success' => false,
            'error' => $errorMessage,
            'details' => $response->json(),
        ];
    }

    public function getStatus(string $documentId): array
    {
        if (empty($this->apiKey)) {
            return ['status' => 'error', 'error' => 'Stripe not configured'];
        }

        $response = Http::withToken($this->apiKey)
            ->get('https://api.stripe.com/v1/charges/' . $documentId);

        if ($response->successful()) {
            $charge = $response->json();

            return [
                'status' => $charge['status'],
                'paid' => $charge['paid'],
                'amount' => $charge['amount'],
                'currency' => $charge['currency'],
                'raw' => $charge,
            ];
        }

        return ['status' => 'error', 'error' => $response->json('error')['message'] ?? 'Unknown error'];
    }

    public function createPaymentIntent(int $amount, string $currency = 'GBP', array $metadata = []): mixed
    {
        return $this->submit('/payment_intents', [
            'payload' => [
                'amount' => $amount,
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => ['enabled' => true],
            ],
        ]);
    }

    public function createCustomer(string $email, array $metadata = []): mixed
    {
        return $this->submit('/customers', [
            'payload' => [
                'email' => $email,
                'metadata' => $metadata,
            ],
        ]);
    }

    public function createInvoice(string $customerId, array $lineItems, array $metadata = []): mixed
    {
        return $this->submit('/invoices', [
            'payload' => [
                'customer' => $customerId,
                'collection_method' => 'charge_automatically',
                'metadata' => $metadata,
            ],
        ]);
    }

    public function getSupportedPaymentMethods(): array
    {
        return [
            'card',
            'apple_pay',
            'google_pay',
            'bacs_debit',
            'sepa_debit',
        ];
    }

    private function formatStripeData(array $data): array
    {
        $formatted = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }
}
