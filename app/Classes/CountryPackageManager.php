<?php

namespace App\Classes;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class CountryPackageManager
{
    protected ?string $activeCountryPackage = null;

    public function discover(): array
    {
        $path = base_path('packages/workdo');
        $packages = [];

        if (!File::isDirectory($path)) {
            return $packages;
        }

        foreach (File::directories($path) as $dir) {
            $name = basename($dir);

            if (!str_starts_with($name, 'Country')) {
                continue;
            }

            $moduleJson = $dir . '/module.json';

            if (!File::exists($moduleJson)) {
                continue;
            }

            $packages[] = [
                'name' => $name,
                'config' => json_decode(File::get($moduleJson), true),
            ];
        }

        return $packages;
    }

    public function loadActive(): ?string
    {
        $countryCode = company_country();
        $packageName = 'Country' . $countryCode;

        $path = base_path("packages/workdo/{$packageName}");

        if (!File::isDirectory($path)) {
            $this->activeCountryPackage = null;
            return null;
        }

        $this->activeCountryPackage = $packageName;

        return $packageName;
    }

    public function getActive(): ?string
    {
        return $this->activeCountryPackage;
    }

    public function isActive(string $packageName): bool
    {
        return $this->activeCountryPackage === $packageName;
    }
}
