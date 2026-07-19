<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Classes\CountryPackageManager;

class CurrencyService
{
    protected array $rates = [];
    protected string $baseCurrency = 'USD';
    protected int $cacheTtl = 3600;

    public function __construct()
    {
        $config = config('countries.currency_conversion');
        $this->baseCurrency = $config['base_currency'] ?? 'USD';
        $this->cacheTtl = $config['update_interval'] ?? 3600;
    }

    public function getRate(string $fromCurrency, string $toCurrency): float
    {
        if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
            return 1.0;
        }

        $key = "currency_rate_{$fromCurrency}_{$toCurrency}";
        
        return Cache::remember($key, $this->cacheTtl, function () use ($fromCurrency, $toCurrency) {
            return $this->fetchRate($fromCurrency, $toCurrency);
        });
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $rate = $this->getRate($fromCurrency, $toCurrency);
        return round($amount * $rate, 2);
    }

    public function format(float $amount, string $currency, ?string $locale = null): string
    {
        $symbol = $this->getSymbol($currency);
        $formatted = number_format($amount, 2);

        $position = $this->getSymbolPosition($currency);

        if ($position === 'before') {
            return "{$symbol}{$formatted}";
        }

        return "{$formatted} {$symbol}";
    }

    public function getSymbol(string $currency): string
    {
        $symbols = [
            'GBP' => '£',
            'USD' => '$',
            'EUR' => '€',
            'AED' => 'د.إ',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'JPY' => '¥',
            'INR' => '₹',
            'SAR' => '﷼',
            'QAR' => '﷼',
            'KWD' => 'د.ك',
            'BHD' => 'د.ب',
            'OMR' => '﷼',
        ];

        return $symbols[strtoupper($currency)] ?? strtoupper($currency);
    }

    public function getSymbolPosition(string $currency): string
    {
        $before = ['GBP', 'USD', 'EUR', 'AUD', 'CAD', 'INR', 'JPY'];
        $after = ['AED', 'SAR', 'QAR', 'KWD', 'BHD', 'OMR'];

        if (in_array(strtoupper($currency), $before)) {
            return 'before';
        }

        if (in_array(strtoupper($currency), $after)) {
            return 'after';
        }

        return 'before';
    }

    public function getCurrencyForCountry(string $countryCode): string
    {
        $currencies = [
            'GB' => 'GBP',
            'US' => 'USD',
            'AE' => 'AED',
            'AU' => 'AUD',
            'CA' => 'CAD',
            'DE' => 'EUR',
            'FR' => 'EUR',
            'IN' => 'INR',
            'JP' => 'JPY',
            'SA' => 'SAR',
            'QA' => 'QAR',
            'KW' => 'KWD',
            'BH' => 'BHD',
            'OM' => 'OMR',
        ];

        return $currencies[strtoupper($countryCode)] ?? 'USD';
    }

    public function getAllRates(string $baseCurrency = 'USD'): array
    {
        $key = "currency_all_rates_{$baseCurrency}";

        return Cache::remember($key, $this->cacheTtl, function () use ($baseCurrency) {
            return $this->fetchAllRates($baseCurrency);
        });
    }

    public function getSupportedCurrencies(): array
    {
        $currencies = [];

        // Get currencies from installed country packages
        $countryManager = app(CountryPackageManager::class);
        $packages = $countryManager->discover();

        foreach ($packages as $package) {
            $countryCode = $package['config']['country_code'] ?? null;
            if ($countryCode) {
                $currency = $this->getCurrencyForCountry($countryCode);
                $currencies[$currency] = [
                    'code' => $currency,
                    'symbol' => $this->getSymbol($currency),
                    'country' => $countryCode,
                    'country_name' => $package['config']['alias'] ?? $countryCode,
                ];
            }
        }

        // Always include USD as base
        if (!isset($currencies['USD'])) {
            $currencies['USD'] = [
                'code' => 'USD',
                'symbol' => '$',
                'country' => 'US',
                'country_name' => 'United States',
            ];
        }

        return $currencies;
    }

    protected function fetchRate(string $from, string $to): float
    {
        $config = config('countries.currency_conversion');
        $provider = $config['provider'] ?? 'openexchangerates';
        $apiKey = $config['api_key'] ?? null;

        if (!$apiKey) {
            Log::warning('Currency API key not configured. Using fallback rate of 1.0');
            return 1.0;
        }

        try {
            $response = match ($provider) {
                'openexchangerates' => $this->fetchFromOpenExchangeRates($from, $to, $apiKey),
                'exchangerate-api' => $this->fetchFromExchangeRateAPI($from, $to, $apiKey),
                'fixer' => $this->fetchFromFixer($from, $to, $apiKey),
                default => $this->fetchFromOpenExchangeRates($from, $to, $apiKey),
            };

            if ($response['success']) {
                return $response['rate'];
            }
        } catch (\Exception $e) {
            Log::error('Currency conversion failed', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }

        return 1.0;
    }

    protected function fetchAllRates(string $baseCurrency): array
    {
        $config = config('countries.currency_conversion');
        $provider = $config['provider'] ?? 'openexchangerates';
        $apiKey = $config['api_key'] ?? null;

        if (!$apiKey) {
            return [$baseCurrency => 1.0];
        }

        try {
            $response = match ($provider) {
                'openexchangerates' => Http::get("https://openexchangerates.org/api/latest.json?app_id={$apiKey}&base={$baseCurrency}"),
                'exchangerate-api' => Http::get("https://api.exchangerate-api.com/v4/latest/{$baseCurrency}"),
                'fixer' => Http::get("http://data.fixer.io/api/latest?access_key={$apiKey}&base={$baseCurrency}"),
                default => Http::get("https://openexchangerates.org/api/latest.json?app_id={$apiKey}&base={$baseCurrency}"),
            };

            if ($response->successful()) {
                return $response->json('rates') ?? [$baseCurrency => 1.0];
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch all currency rates', [
                'base' => $baseCurrency,
                'error' => $e->getMessage(),
            ]);
        }

        return [$baseCurrency => 1.0];
    }

    protected function fetchFromOpenExchangeRates(string $from, string $to, string $apiKey): array
    {
        $response = Http::get("https://openexchangerates.org/api/latest.json?app_id={$apiKey}&base={$from}");

        if ($response->successful()) {
            $rates = $response->json('rates') ?? [];
            $rate = $rates[$to] ?? null;

            if ($rate) {
                return ['success' => true, 'rate' => (float) $rate];
            }
        }

        return ['success' => false];
    }

    protected function fetchFromExchangeRateAPI(string $from, string $to, string $apiKey): array
    {
        $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$from}");

        if ($response->successful()) {
            $rates = $response->json('rates') ?? [];
            $rate = $rates[$to] ?? null;

            if ($rate) {
                return ['success' => true, 'rate' => (float) $rate];
            }
        }

        return ['success' => false];
    }

    protected function fetchFromFixer(string $from, string $to, string $apiKey): array
    {
        $response = Http::get("http://data.fixer.io/api/latest?access_key={$apiKey}&base={$from}&symbols={$to}");

        if ($response->successful()) {
            $rates = $response->json('rates') ?? [];
            $rate = $rates[$to] ?? null;

            if ($rate) {
                return ['success' => true, 'rate' => (float) $rate];
            }
        }

        return ['success' => false];
    }
}
