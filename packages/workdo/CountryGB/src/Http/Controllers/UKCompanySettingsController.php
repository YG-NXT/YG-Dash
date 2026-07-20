<?php

namespace Workdo\CountryGB\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\CountryGB\Models\UKCompanySetting;
use Workdo\CountryGB\Services\UKValidationService;
use Workdo\CountryGB\Services\UKOnboardingService;

class UKCompanySettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $settings = UKCompanySetting::where('created_by', $user->id)->first();

        if (!$settings) {
            $settings = new UKCompanySetting(['created_by' => $user->id]);
        }

        $onboardingService = new UKOnboardingService();

        return Inertia::render('UK/Settings', [
            'settings' => $settings,
            'onboardingSteps' => $onboardingService->getOnboardingSteps(),
            'requiredIntegrations' => $onboardingService->getRequiredGovernmentIntegrations(),
            'recommendedGateways' => $onboardingService->getRecommendedPaymentGateways(),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $settings = UKCompanySetting::where('created_by', $user->id)->firstOrFail();

        $validated = $request->validate([
            'vat_number' => 'nullable|string|max:50',
            'company_number' => 'nullable|string|max:50',
            'utr' => 'nullable|string|max:50',
            'paye_reference' => 'nullable|string|max:50',
            'accounts_office_reference' => 'nullable|string|max:50',
            'cis_contractor_number' => 'nullable|string|max:50',
            'vat_scheme' => 'nullable|string|in:standard,flat_rate,cash_scheme,annual',
            'fiscal_year_end' => 'nullable|string|max:5',
            'hmrc_client_id' => 'nullable|string|max:255',
            'hmrc_client_secret' => 'nullable|string|max:255',
            'hmrc_access_token' => 'nullable|string',
            'hmrc_refresh_token' => 'nullable|string',
            'hmrc_token_expires_at' => 'nullable|date',
            'companies_house_api_key' => 'nullable|string|max:255',
            'nhs_api_key' => 'nullable|string|max:255',
            'cqc_api_key' => 'nullable|string|max:255',
            'vat_registered' => 'boolean',
            'cis_registered' => 'boolean',
            'paye_registered' => 'boolean',
        ]);

        $validated['created_by'] = $user->id;
        $validated['country_code'] = 'GB';

        $settings->update($validated);

        // Also update the main User model for quick access
        $user->update([
            'country_code' => 'GB',
            'timezone' => $request->timezone ?? 'Europe/London',
            'locale' => $request->locale ?? 'en-GB',
        ]);

        return back()->with('success', __('Settings saved successfully'));
    }

    public function validateField(Request $request)
    {
        $request->validate([
            'field' => 'required|string|in:postcode,vat_number,company_number,phone,utr,nino,cis_number,sort_code,account_number',
            'value' => 'required|string',
        ]);

        $validationService = new UKValidationService();
        $field = $request->field;
        $value = $request->value;

        $result = match ($field) {
            'postcode' => $validationService->validatePostcode($value),
            'vat_number' => $validationService->validateVATNumber($value),
            'company_number' => $validationService->validateCompanyNumber($value),
            'phone' => $validationService->validatePhoneNumber($value),
            'utr' => $validationService->validateUTR($value),
            'nino' => $validationService->validateNINO($value),
            'cis_number' => $validationService->validateCISNumber($value),
            default => ['valid' => false, 'error' => 'Unknown field'],
        };

        return response()->json($result);
    }
}

