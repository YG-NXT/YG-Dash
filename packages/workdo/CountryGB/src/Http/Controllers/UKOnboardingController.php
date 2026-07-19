<?php

namespace Workdo\CountryGB\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\CountryGB\Services\UKOnboardingService;
use Workdo\CountryGB\Models\UKCompanySetting;

class UKOnboardingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Check if onboarding is already completed
        $settings = UKCompanySetting::where('created_by', $user->id)->first();
        
        if ($settings && $settings->vat_number) {
            return redirect()->route('uk.settings');
        }

        $onboardingService = new UKOnboardingService();

        return Inertia::render('UK/Onboarding', [
            'onboardingSteps' => $onboardingService->getOnboardingSteps(),
            'requiredIntegrations' => $onboardingService->getRequiredGovernmentIntegrations(),
            'recommendedGateways' => $onboardingService->getRecommendedPaymentGateways(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $onboardingService = new UKOnboardingService();

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'utr' => 'nullable|string|max:50',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'vat_registered' => 'boolean',
            'vat_scheme' => 'nullable|string|in:standard,flat_rate,cash_scheme,annual',
            'fiscal_year_end' => 'nullable|string|max:5',
            'paye_reference' => 'nullable|string|max:50',
            'cis_contractor_number' => 'nullable|string|max:50',
            'accounts_office_reference' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'sort_code' => 'nullable|string|max:20',
            'account_number' => 'nullable|string|max:20',
            'account_name' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'date_format' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
        ]);

        // Create or update UK company settings
        $settings = UKCompanySetting::updateOrCreate(
            ['created_by' => $user->id],
            [
                'country_code' => 'GB',
                'vat_number' => $validated['vat_number'] ?? null,
                'company_number' => $validated['company_number'] ?? null,
                'utr' => $validated['utr'] ?? null,
                'paye_reference' => $validated['paye_reference'] ?? null,
                'accounts_office_reference' => $validated['accounts_office_reference'] ?? null,
                'cis_contractor_number' => $validated['cis_contractor_number'] ?? null,
                'vat_scheme' => $validated['vat_scheme'] ?? 'standard',
                'fiscal_year_end' => $validated['fiscal_year_end'] ?? '03-31',
                'vat_registered' => $validated['vat_registered'] ?? false,
                'cis_registered' => !empty($validated['cis_contractor_number']),
                'paye_registered' => !empty($validated['paye_reference']),
            ]
        );

        // Update user settings
        $user->update([
            'country_code' => 'GB',
            'timezone' => $validated['timezone'] ?? 'Europe/London',
            'locale' => $validated['language'] ?? 'en-GB',
        ]);

        // Apply default UK settings
        $defaultSettings = $onboardingService->getDefaultSettings();
        foreach ($defaultSettings as $key => $value) {
            setSetting($key, $value, $user->id);
        }

        return redirect()->route('dashboard')->with('success', 'Your UK account is ready. Welcome to DashSaaS!');
    }
}
