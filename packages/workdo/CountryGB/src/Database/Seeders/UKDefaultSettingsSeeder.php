<?php

namespace Workdo\CountryGB\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\CountryGB\Services\UKOnboardingService;

class UKDefaultSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $onboardingService = new UKOnboardingService();
        $defaultSettings = $onboardingService->getDefaultSettings();

        foreach ($defaultSettings as $key => $value) {
            // During installation, creatorId() may return null, so we pass it explicitly
            // The setSetting function will handle null by using 0 as fallback
            setSetting($key, $value, creatorId());
        }
    }
}