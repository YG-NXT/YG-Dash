<?php

namespace Workdo\CountryGB\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\CountryGB\Services\HMRCVATService;
use Workdo\CountryGB\Models\UKCompanySetting;

class HMRCOAuthController extends Controller
{
    public function redirect()
    {
        $user = Auth::user();
        $settings = UKCompanySetting::where('created_by', $user->id)->first();

        if (!$settings || empty($settings->hmrc_client_id)) {
            return redirect()->route('uk.settings')->with('error', 'Please configure your HMRC Client ID first.');
        }

        $hmrcService = new HMRCVATService();
        $authUrl = $hmrcService->getAuthUrl(
            $settings->hmrc_client_id,
            route('uk.hmrc.callback'),
            ['read:vat', 'write:vat', 'read:payroll', 'write:payroll']
        );

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $settings = UKCompanySetting::where('created_by', $user->id)->firstOrFail();

        $credentials = [
            'client_id' => $settings->hmrc_client_id,
            'client_secret' => $settings->hmrc_client_secret,
            'redirect_uri' => route('uk.hmrc.callback'),
            'authorization_code' => $request->code,
        ];

        $hmrcService = new HMRCVATService();
        $result = $hmrcService->authenticate($credentials);

        if ($result['success']) {
            $settings->update([
                'hmrc_access_token' => $result['access_token'],
                'hmrc_refresh_token' => $result['refresh_token'],
                'hmrc_token_expires_at' => now()->addSeconds($result['expires_in']),
            ]);

            return redirect()->route('uk.settings')->with('success', 'Successfully connected to HMRC.');
        }

        return redirect()->route('uk.settings')->with('error', $result['error'] ?? 'HMRC authentication failed.');
    }
}
