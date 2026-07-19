<?php

use Illuminate\Support\Facades\Route;
use Workdo\CountryGB\Http\Controllers\UKCompanySettingsController;
use Workdo\CountryGB\Http\Controllers\UKPayrollDocumentsController;

Route::middleware(['web', 'auth'])->prefix('uk')->name('uk.')->group(function () {
    Route::get('/settings', [UKCompanySettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [UKCompanySettingsController::class, 'update'])->name('settings.update');
    Route::post('/validate', [UKCompanySettingsController::class, 'validateField'])->name('validate.field');

    // UK payroll documents
    Route::get('/payroll/p45/{payrollEntry}', [UKPayrollDocumentsController::class, 'p45'])->name('payroll.p45');
    Route::get('/payroll/p60/{payrollEntry}', [UKPayrollDocumentsController::class, 'p60'])->name('payroll.p60');
    Route::get('/payroll/p11d/{payrollEntry}', [UKPayrollDocumentsController::class, 'p11d'])->name('payroll.p11d');
});
