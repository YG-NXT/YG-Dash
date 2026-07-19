<?php

namespace Workdo\CountryGB\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Workdo\CountryGB\Hooks\CountryGBHooks;

class CountryGBServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (company_country() === 'GB') {
            CountryGBHooks::register();
        }

        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('uk')
            ->name('uk.')
            ->group(function () {
                Route::get('/settings', [\Workdo\CountryGB\Http\Controllers\UKCompanySettingsController::class, 'index'])->name('settings');
                Route::post('/settings', [\Workdo\CountryGB\Http\Controllers\UKCompanySettingsController::class, 'update'])->name('settings.update');
                Route::post('/validate', [\Workdo\CountryGB\Http\Controllers\UKCompanySettingsController::class, 'validateField'])->name('validate.field');
                Route::get('/hmrc/redirect', [\Workdo\CountryGB\Http\Controllers\HMRCOAuthController::class, 'redirect'])->name('hmrc.redirect');
                Route::get('/hmrc/callback', [\Workdo\CountryGB\Http\Controllers\HMRCOAuthController::class, 'callback'])->name('hmrc.callback');
                Route::get('/onboarding', [\Workdo\CountryGB\Http\Controllers\UKOnboardingController::class, 'index'])->name('onboarding');
                Route::post('/onboarding', [\Workdo\CountryGB\Http\Controllers\UKOnboardingController::class, 'store'])->name('onboarding.store');
            });
    }
}
