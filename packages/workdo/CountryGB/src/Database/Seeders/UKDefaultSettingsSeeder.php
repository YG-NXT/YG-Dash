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
            setSetting($key, $value, creatorId());
        }
    }
}
