<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Classes\CountryPackageManager;

class CountryDetectionService
{
    protected array $supportedCountries = [];
    protected int $cacheTtl = 86400; // 24 hours

    public function __construct()
    {
        $this->loadSupportedCountries();
    }

    public function detect(Request $request): string
    {
        // 1. Check logged-in user's country
        $userCountry = $this->detectFromUser();
        if ($userCountry) {
            return $userCountry;
        }

        // 2. Check URL path
        $pathCountry = $this->detectFromPath($request);
        if ($pathCountry && $this->isSupported($pathCountry)) {
            return $pathCountry;
        }

        // 3. Check subdomain
        $subdomainCountry = $this->detectFromSubdomain($request);
        if ($subdomainCountry && $this->isSupported($subdomainCountry)) {
            return $subdomainCountry;
        }

        // 4. Check session
        $sessionCountry = $this->detectFromSession($request);
        if ($sessionCountry && $this->isSupported($sessionCountry)) {
            return $sessionCountry;
        }

        // 5. Check IP geolocation
        $ipCountry = $this->detectFromIP($request);
        if ($ipCountry && $this->isSupported($ipCountry)) {
            return $ipCountry;
        }

        // 6. Check Accept-Language
        $langCountry = $this->detectFromLanguage($request);
        if ($langCountry && $this->isSupported($langCountry)) {
            return $langCountry;
        }

        // 7. Default
        return config('countries.default', 'US');
    }

    protected function detectFromUser(): ?string
    {
        if (auth()->check() && auth()->user()->country_code) {
            return auth()->user()->country_code;
        }

        return null;
    }

    protected function detectFromPath(Request $request): ?string
    {
        $path = $request->path();
        $segment = explode('/', $path)[0] ?? '';

        $countryMap = $this->getCountryAliases();

        return $countryMap[strtolower($segment)] ?? null;
    }

    protected function detectFromSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        $subdomain = strtolower($parts[0] ?? '');

        $countryMap = $this->getCountryAliases();

        return $countryMap[$subdomain] ?? null;
    }

    protected function detectFromSession(Request $request): ?string
    {
        return $request->session()->get('country_code');
    }

    protected function detectFromIP(Request $request): ?string
    {
        $ip = $request->ip();

        if ($this->isLocalhost($ip)) {
            return null;
        }

        $key = "country_ip_{$ip}";

        return Cache::remember($key, $this->cacheTtl, function () use ($ip) {
            return $this->lookupIP($ip);
        });
    }

    protected function detectFromLanguage(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language', '');
        $primaryLang = explode(',', $acceptLanguage)[0] ?? 'en';

        $languageToCountry = [
            'en-GB' => 'GB',
            'en-US' => 'US',
            'en-AU' => 'AU',
            'en-CA' => 'CA',
            'ar-AE' => 'AE',
            'ar-SA' => 'SA',
            'ar-QA' => 'QA',
            'ar-KW' => 'KW',
            'ar-BH' => 'BH',
            'ar-OM' => 'OM',
            'de-DE' => 'DE',
            'de-AT' => 'AT',
            'fr-FR' => 'FR',
            'ja-JP' => 'JP',
            'hi-IN' => 'IN',
        ];

        return $languageToCountry[$primaryLang] ?? null;
    }

    protected function lookupIP(string $ip): ?string
    {
        $config = config('countries.geolocation');
        $provider = $config['provider'] ?? 'ip-api';
        $apiKey = $config['api_key'] ?? null;

        try {
            $response = match ($provider) {
                'ip-api' => $this->lookupWithIPAPI($ip, $apiKey),
                'maxmind' => $this->lookupWithMaxMind($ip, $apiKey),
                'ipstack' => $this->lookupWithIPStack($ip, $apiKey),
                default => $this->lookupWithIPAPI($ip, $apiKey),
            };

            if ($response['success'] && $response['country_code']) {
                return strtoupper($response['country_code']);
            }
        } catch (\Exception $e) {
            Log::warning('IP geolocation failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function lookupWithIPAPI(string $ip, ?string $apiKey): array
    {
        $url = $apiKey
            ? "http://api.ipapi.com/{$ip}?access_key={$apiKey}"
            : "http://ip-api.com/json/{$ip}";

        $response = Http::timeout(config('countries.geolocation.timeout', 5))->get($url);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'country_code' => $data['country_code'] ?? $data['countryCode'] ?? null,
                'country_name' => $data['country_name'] ?? $data['country'] ?? null,
            ];
        }

        return ['success' => false];
    }

    protected function lookupWithMaxMind(string $ip, ?string $apiKey): array
    {
        if (!$apiKey) {
            return ['success' => false];
        }

        $response = Http::timeout(config('countries.geolocation.timeout', 5))
            ->withHeaders(['Authorization' => "Basic " . base64_encode($apiKey . ':')])
            ->get("https://geoip.maxmind.com/geoip/v2.1/country/{$ip}");

        if ($response->successful()) {
            $data = $response->json();
            $country = $data['country'] ?? [];

            return [
                'success' => true,
                'country_code' => $country['iso_code'] ?? null,
                'country_name' => $country['names']['en'] ?? null,
            ];
        }

        return ['success' => false];
    }

    protected function lookupWithIPStack(string $ip, ?string $apiKey): array
    {
        if (!$apiKey) {
            return ['success' => false];
        }

        $response = Http::timeout(config('countries.geolocation.timeout', 5))
            ->get("http://api.ipstack.com/{$ip}?access_key={$apiKey}");

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'country_code' => $data['country_code'] ?? null,
                'country_name' => $data['country_name'] ?? null,
            ];
        }

        return ['success' => false];
    }

    protected function isLocalhost(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) ||
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.');
    }

    public function isSupported(string $countryCode): bool
    {
        return isset($this->supportedCountries[strtoupper($countryCode)]);
    }

    public function getSupportedCountries(): array
    {
        return $this->supportedCountries;
    }

    public static function getAvailableCountryCodes(): array
    {
        return array_keys((new static())->getSupportedCountries());
    }

    protected function loadSupportedCountries(): void
    {
        // Load from installed country packages
        $countryManager = app(CountryPackageManager::class);
        $packages = $countryManager->discover();

        foreach ($packages as $package) {
            $countryCode = $package['config']['country_code'] ?? null;
            if ($countryCode) {
                $this->supportedCountries[$countryCode] = [
                    'code' => $countryCode,
                    'name' => $package['config']['alias'] ?? $countryCode,
                    'package' => $package['name'],
                ];
            }
        }

        // Always include default
        if (!isset($this->supportedCountries[config('countries.default', 'US')])) {
            $this->supportedCountries[config('countries.default', 'US')] = [
                'code' => 'US',
                'name' => 'United States',
                'package' => null,
            ];
        }
    }

    protected function getCountryAliases(): array
    {
        return [
            'gb' => 'GB',
            'uk' => 'GB',
            'us' => 'US',
            'usa' => 'US',
            'ae' => 'AE',
            'uae' => 'AE',
            'au' => 'AU',
            'de' => 'DE',
            'fr' => 'FR',
            'in' => 'IN',
            'jp' => 'JP',
            'sa' => 'SA',
            'qa' => 'QA',
            'kw' => 'KW',
            'bh' => 'BH',
            'om' => 'OM',
            'ca' => 'CA',
            'at' => 'AT',
        ];
    }
}

