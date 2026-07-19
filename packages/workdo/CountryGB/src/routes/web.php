<?php

use Illuminate\Support\Facades\Route;
use Workdo\CountryGB\Http\Controllers\UKCompanySettingsController;

Route::middleware(['web', 'auth'])->prefix('uk')->name('uk.')->group(function () {
    Route::get('/settings', [UKCompanySettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [UKCompanySettingsController::class, 'update'])->name('settings.update');
    Route::post('/validate', [UKCompanySettingsController::class, 'validateField'])->name('validate.field');
});
