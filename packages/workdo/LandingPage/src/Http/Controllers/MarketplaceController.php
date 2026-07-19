<?php

namespace Workdo\LandingPage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;
use App\Models\AddOn;
use App\Models\User;
use Workdo\LandingPage\Models\MarketplaceSetting;
use Workdo\LandingPage\Models\LandingPageSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Workdo\LandingPage\Http\Requests\StoreMarketplaceSettingRequest;
use App\Classes\Hooks;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = AddOn::where('is_enable', true)->whereNotIn('module', User::$superadmin_activated_module);
        $packages = $query->get();
        
        // Find the package matching the given slug
        $matchedPackage = $packages->firstWhere('package_name', $request->slug);

        // If no package found, show 404 page
        if (!$matchedPackage) {
            $landingPageSettings = LandingPageSetting::first();
            return Inertia::render('LandingPage/MarketplaceNotFound', [
                'landingPageSettings' => $landingPageSettings
            ]);
        }

        // Use the "name" field (module name style)
        $moduleName = $matchedPackage->module;

        $settings = MarketplaceSetting::where('module', $moduleName)->first();
        $landingPageSettings = LandingPageSetting::first();
        
        $settingsArray = $settings ? $settings->toArray() : [];
        
        // Apply country-specific marketplace hooks
        $company = Auth::user();
        if ($company) {
            $settingsArray['hero'] = Hooks::apply_filters('marketplace_hero', $settingsArray['config_sections']['sections']['hero'] ?? [], $company);
            $settingsArray['features'] = Hooks::apply_filters('marketplace_features', $settingsArray['config_sections']['sections']['features'] ?? [], $company);
            $settingsArray['why_choose'] = Hooks::apply_filters('marketplace_why_choose', $settingsArray['config_sections']['sections']['why_choose'] ?? [], $company);
            $settingsArray['cta'] = Hooks::apply_filters('marketplace_cta', $settingsArray['config_sections']['sections']['cta'] ?? [], $company);
            $settingsArray['country_code'] = $company->country_code;
        } else {
            // Use detected/default country for public marketplace page
            $detectedCountry = $this->detectVisitorCountry($request) ?? ($landingPageSettings->default_country ?? 'US');
            $fakeCompany = new \stdClass();
            $fakeCompany->country_code = strtoupper($detectedCountry);

            $settingsArray['hero'] = Hooks::apply_filters('marketplace_hero', $settingsArray['config_sections']['sections']['hero'] ?? [], $fakeCompany);
            $settingsArray['features'] = Hooks::apply_filters('marketplace_features', $settingsArray['config_sections']['sections']['features'] ?? [], $fakeCompany);
            $settingsArray['why_choose'] = Hooks::apply_filters('marketplace_why_choose', $settingsArray['config_sections']['sections']['why_choose'] ?? [], $fakeCompany);
            $settingsArray['cta'] = Hooks::apply_filters('marketplace_cta', $settingsArray['config_sections']['sections']['cta'] ?? [], $fakeCompany);
            $settingsArray['country_code'] = strtoupper($detectedCountry);
        }
        
        return Inertia::render('LandingPage/Marketplace', [
            'packages' => $packages,
            'settings' => $settingsArray,
            'landingPageSettings' => $landingPageSettings
        ]);
    }

    public function settings(Request $request)
    {
        if(Auth::user()->can('manage-marketplace-settings')){
            $module = $request->get('module');
            $settings = null;
            
            if ($module) {
                $settings = MarketplaceSetting::where('module', $module)->first();
            }
            
            $activeModules = AddOn::where('is_enable', true)->get(['name', 'module', 'package_name'])->map(function($addon) {
                return [
                    'module' => $addon->module,
                    'name' => $addon->name,
                    'version' => $addon->version ?? '1.0.0',
                    'package_name' => $addon->package_name
                ];
            });
        
            return Inertia::render('LandingPage/marketplace/Settings', [
                'settings' => $settings ?: [
                    'module' => $module,
                    'title' => 'Marketplace',
                    'config_sections' => []
                ],
                'activeModules' => $activeModules,
                'selectedModule' => $module
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function storeSettings(StoreMarketplaceSettingRequest $request)
    {
        if(Auth::user()->can('manage-marketplace-settings')){
            $validated = $request->validated();

            // Handle image paths - store only filename
            if (isset($validated['config_sections']['sections'])) {
                $this->processImagePaths($validated['config_sections']['sections']);
            }

            MarketplaceSetting::updateOrCreate(['module' => $validated['module']], $validated);
        
            return back()->with('success', __('Marketplace settings saved successfully'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    private function processImagePaths(&$sections)
    {
        foreach ($sections as $sectionKey => &$sectionData) {
            if (is_array($sectionData)) {
                // Handle hero image
                if (isset($sectionData['image'])) {
                    $sectionData['image'] = $this->processImagePath($sectionData['image']);
                }
                
                // Handle screenshots images array
                if (isset($sectionData['images']) && is_array($sectionData['images'])) {
                    $sectionData['images'] = array_map([$this, 'processImagePath'], $sectionData['images']);
                }
                
                // Handle dedication subSections screenshots
                if (isset($sectionData['subSections']) && is_array($sectionData['subSections'])) {
                    foreach ($sectionData['subSections'] as &$subSection) {
                        if (isset($subSection['screenshot'])) {
                            $subSection['screenshot'] = $this->processImagePath($subSection['screenshot']);
                        }
                    }
                }
            }
        }
    }

    private function processImagePath($imagePath)
    {
        if (strpos($imagePath, 'packages/workdo') !== false) {
            return $imagePath;
        }
        return basename($imagePath);
    }

    private function detectVisitorCountry(Request $request): ?string
    {
        $ip = $request->ip();
        
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            return null;
        }

        $cacheKey = 'visitor_country_' . md5($ip);
        
        return Cache::remember($cacheKey, 86400, function () use ($ip) {
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=countryCode");
                
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['countryCode'] ?? null;
                }
            } catch (\Exception $e) {
                \Log::warning('IP geolocation failed: ' . $e->getMessage());
            }
            
            return null;
        });
    }


}