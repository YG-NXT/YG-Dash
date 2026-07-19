<?php

namespace Workdo\CountryGB\Services;

class UKPaymentGatewayService
{
    private array $supportedMethods = [
        'stripe' => [
            'name' => 'Stripe UK',
            'currencies' => ['GBP'],
            'methods' => ['card', 'apple_pay', 'google_pay', 'bacs_debit'],
        ],
        'gocardless' => [
            'name' => 'GoCardless',
            'currencies' => ['GBP'],
            'methods' => ['direct_debit', 'instant_bank_pay'],
        ],
        'paypal' => [
            'name' => 'PayPal UK',
            'currencies' => ['GBP', 'EUR'],
            'methods' => ['paypal', 'credit_card'],
        ],
        'bacs' => [
            'name' => 'BACS Bank Transfer',
            'currencies' => ['GBP'],
            'methods' => ['bank_transfer'],
        ],
    ];

    public function getSupportedGateways(): array
    {
        return $this->supportedMethods;
    }

    public function getDefaultCurrency(): string
    {
        return 'GBP';
    }

    public function getCurrencySymbol(): string
    {
        return '&pound;';
    }

    public function getRecommendedGateway(string $useCase = 'general'): array
    {
        $recommendations = [
            'general' => 'stripe',
            'subscription' => 'stripe',
            'direct_debit' => 'gocardless',
            'b2b' => 'gocardless',
            'one_off' => 'stripe',
        ];

        $gateway = $recommendations[$useCase] ?? 'stripe';

        return [
            'gateway' => $gateway,
            'config' => $this->supportedMethods[$gateway],
        ];
    }

    public function getBACSFormat(string $sortCode, string $accountNumber): array
    {
        $validation = app(UKValidationService::class)->validateSortCodeAccountNumber($sortCode, $accountNumber);

        if (!$validation['valid']) {
            return [
                'valid' => false,
                'error' => $validation['error'],
            ];
        }

        return [
            'valid' => true,
            'formatted' => 'Sort Code: ' . $validation['sort_code'] . ', Account: ' . $validation['account_number'],
            'bacs_ready' => true,
        ];
    }

    public function getPaymentTermsOptions(): array
    {
        return [
            ['value' => 'net_0', 'label' => 'Immediate', 'days' => 0],
            ['value' => 'net_7', 'label' => 'Net 7 days', 'days' => 7],
            ['value' => 'net_14', 'label' => 'Net 14 days', 'days' => 14],
            ['value' => 'net_30', 'label' => 'Net 30 days', 'days' => 30],
            ['value' => 'net_60', 'label' => 'Net 60 days', 'days' => 60],
            ['value' => 'net_90', 'label' => 'Net 90 days', 'days' => 90],
        ];
    }

    public function getBankDetailsRequirements(): array
    {
        return [
            'sort_code' => [
                'label' => 'Sort Code',
                'format' => '12-34-56',
                'validation' => 'required|regex:/^\d{2}-\d{2}-\d{2}$/',
            ],
            'account_number' => [
                'label' => 'Account Number',
                'format' => '12345678',
                'validation' => 'required|regex:/^\d{8}$/',
            ],
            'account_name' => [
                'label' => 'Account Name',
                'format' => 'Text',
                'validation' => 'required|string|max:255',
            ],
            'bank_name' => [
                'label' => 'Bank Name',
                'format' => 'Text',
                'validation' => 'nullable|string|max:255',
            ],
        ];
    }
}
