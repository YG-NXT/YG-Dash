<?php

namespace Workdo\LandingPage\Http\Controllers;

use App\Classes\Hooks;
use App\Classes\CountryPackageManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use App\Models\AddOn;
use App\Models\Plan;
use App\Models\User;
use Workdo\LandingPage\Models\LandingPageSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Workdo\LandingPage\Models\CustomPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Workdo\LandingPage\Http\Requests\StoreLandingPageRequest;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {
        $settings = Cache::remember('landing_page_settings', 3600, function () {
            return LandingPageSetting::first();
        });

        if (!isLandingPageEnabled()) {
            $enableRegistration = admin_setting('enableRegistration');

            return Inertia::render('auth/login', [
                'canResetPassword' => Route::has('password.request'),
                'status' => session('status'),
                'enableRegistration' => $enableRegistration === 'on',
            ]);
        }

        $enableRegistration = admin_setting('enableRegistration');

        $settingsData = $settings ? $settings->toArray() : [];
        $settingsData['enable_registration'] = $enableRegistration === 'on';
        $settingsData['is_authenticated'] = $request->user() !== null;

        // Apply country-specific landing page hooks
        $company = Auth::user();
        if ($company) {
            $settingsData['hero'] = Hooks::apply_filters('landing_page_hero', $settingsData['config_sections']['sections']['hero'] ?? [], $company);
            $settingsData['features'] = Hooks::apply_filters('landing_page_features', $settingsData['config_sections']['sections']['features'] ?? [], $company);
            $settingsData['pricing'] = Hooks::apply_filters('landing_page_pricing', $settingsData['config_sections']['sections']['pricing'] ?? [], $company);
            $settingsData['compliance'] = Hooks::apply_filters('landing_page_compliance', [], $company);
            $settingsData['testimonials'] = Hooks::apply_filters('landing_page_testimonials', [], $company);
            $settingsData['cta'] = Hooks::apply_filters('landing_page_cta', $settingsData['config_sections']['sections']['cta'] ?? [], $company);
        } else {
            // Use detected/default country for public landing page
            $detectedCountry = $this->detectVisitorCountry($request) ?? $settingsData['default_country'] ?? 'US';
            $fakeCompany = new \stdClass();
            $fakeCompany->country_code = strtoupper($detectedCountry);

            $settingsData['hero'] = Hooks::apply_filters('landing_page_hero', $settingsData['config_sections']['sections']['hero'] ?? [], $fakeCompany);
            $settingsData['features'] = Hooks::apply_filters('landing_page_features', $settingsData['config_sections']['sections']['features'] ?? [], $fakeCompany);
            $settingsData['pricing'] = Hooks::apply_filters('landing_page_pricing', $settingsData['config_sections']['sections']['pricing'] ?? [], $fakeCompany);
            $settingsData['compliance'] = Hooks::apply_filters('landing_page_compliance', [], $fakeCompany);
            $settingsData['testimonials'] = Hooks::apply_filters('landing_page_testimonials', [], $fakeCompany);
            $settingsData['cta'] = Hooks::apply_filters('landing_page_cta', $settingsData['config_sections']['sections']['cta'] ?? [], $fakeCompany);
        }

        return Inertia::render('LandingPage/Landing', [
            'auth' => [
                'user' => $request->user(),
                'lang' => app()->getLocale()
            ],
            'settings' => $settingsData
        ]);
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

    public function seo(Request $request, $country = null)
    {
        $settings = Cache::remember('landing_page_settings', 3600, function () {
            return LandingPageSetting::first();
        });

        if (!isLandingPageEnabled()) {
            return redirect()->route('dashboard');
        }

        $countryCode = strtoupper($country ?? 'US');
        
        // Create a fake company object for hooks
        $company = new \stdClass();
        $company->country_code = $countryCode;
        
        $settingsData = $settings ? $settings->toArray() : [];
        
        // Apply country-specific landing page hooks
        $hero = Hooks::apply_filters('landing_page_hero', $settingsData['config_sections']['sections']['hero'] ?? [], $company);
        $features = Hooks::apply_filters('landing_page_features', $settingsData['config_sections']['sections']['features'] ?? [], $company);
        $pricing = Hooks::apply_filters('landing_page_pricing', $settingsData['config_sections']['sections']['pricing'] ?? [], $company);
        $compliance = Hooks::apply_filters('landing_page_compliance', [], $company);
        $testimonials = Hooks::apply_filters('landing_page_testimonials', [], $company);
        $cta = Hooks::apply_filters('landing_page_cta', $settingsData['config_sections']['sections']['cta'] ?? [], $company);

        return view('landing-page.country', [
            'hero' => $hero,
            'features' => $features,
            'pricing' => $pricing,
            'compliance' => $compliance,
            'testimonials' => $testimonials,
            'cta' => $cta,
            'country' => $countryCode,
        ]);
    }

    public function addons(Request $request)
    {
        $landingPageSettings = LandingPageSetting::first();
        $query = AddOn::where('is_enable', true)->whereNotIn('module', User::$superadmin_activated_module);
        
        // Search filter
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Category filter (if you have categories)
        if ($request->filled('category')) {
            $query->where('module', $request->category);
        }
        
        // Price filter
        if ($request->filled('price')) {
            $priceColumn = $request->get('price_type', 'monthly') === 'yearly' ? 'yearly_price' : 'monthly_price';
            
            switch ($request->price) {
                case 'free':
                    $query->where(function($q) use ($priceColumn) {
                        $q->whereNull($priceColumn)->orWhere($priceColumn, 0);
                    });
                    break;
                case '0-50':
                    $query->whereBetween($priceColumn, [0.00, 50]);
                    break;
                case '50-100':
                    $query->whereBetween($priceColumn, [50.00, 100]);
                    break;
                case '100+':
                    $query->where($priceColumn, '>', 100);
                    break;
            }
        }
        
        // Sorting
        $priceColumn = $request->get('price_type', 'monthly') === 'yearly' ? 'yearly_price' : 'monthly_price';
        
        switch ($request->get('sort', 'name')) {
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'price_low':
                $query->orderBy($priceColumn, 'asc');
                break;
            case 'price_high':
                $query->orderBy($priceColumn, 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('name', 'asc');
                break;
        }
        
        $addons = $query->paginate($landingPageSettings->config_sections['sections']['addons']['per_page'] ?? 20);
        $categories = AddOn::where('is_enable', true)
            ->distinct()
            ->pluck('module')
            ->filter()
            ->values();
        
        

        return Inertia::render('LandingPage/Addons', [
            'addons' => $addons,
            'settings' => $landingPageSettings,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'price', 'price_type', 'sort'])
        ]);
    }

    public function pricing(Request $request)
    {
        // Get active plans from the main app
        $plans = Plan::where('status', true)
            ->where('custom_plan', false)
            ->withCount('orders')
            ->get();

        // Get active modules/addons
        $activeModules = AddOn::where('is_enable', true)
            ->whereNotIn('module', User::$superadmin_activated_module)
            ->select('module', 'name as alias', 'image', 'monthly_price', 'yearly_price')
            ->get();

        $landingPageSettings = LandingPageSetting::first();
        $enableRegistration = admin_setting('enableRegistration');

        $settingsData = $landingPageSettings ? $landingPageSettings->toArray() : [];
        $settingsData['enable_registration'] = $enableRegistration === 'on';
        $settingsData['is_authenticated'] = $request->user() !== null;

        return Inertia::render('LandingPage/Pricing', [
            'plans' => $plans->map(function($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'package_price_monthly' => $plan->package_price_monthly,
                    'package_price_yearly' => $plan->package_price_yearly,
                    'number_of_users' => $plan->number_of_users,
                    'storage_limit' => $plan->storage_limit,
                    'modules' => $plan->modules ?? [],
                    'free_plan' => $plan->free_plan,
                    'trial' => $plan->trial,
                    'trial_days' => $plan->trial_days,
                    'orders_count' => $plan->orders_count
                ];
            }),
            'activeModules' => $activeModules,
            'settings' => $settingsData,

        ]);
    }

    public function settings()
    {
        if(Auth::user()->can('manage-landing-page')){
            $settings = LandingPageSetting::first();
            $customPages = CustomPage::where('is_active', true)->select('id', 'title', 'slug')->get();

            $availableCountries = [
                ['code' => 'GB', 'name' => 'United Kingdom', 'flag' => '🇬🇧'],
                ['code' => 'US', 'name' => 'United States', 'flag' => '🇺🇸'],
                ['code' => 'AE', 'name' => 'United Arab Emirates', 'flag' => '🇦🇪'],
                ['code' => 'AU', 'name' => 'Australia', 'flag' => '🇦🇺'],
                ['code' => 'DE', 'name' => 'Germany', 'flag' => '🇩🇪'],
                ['code' => 'FR', 'name' => 'France', 'flag' => '🇫🇷'],
                ['code' => 'IN', 'name' => 'India', 'flag' => '🇮🇳'],
                ['code' => 'JP', 'name' => 'Japan', 'flag' => '🇯🇵'],
                ['code' => 'SA', 'name' => 'Saudi Arabia', 'flag' => '🇸🇦'],
                ['code' => 'QA', 'name' => 'Qatar', 'flag' => '🇶🇦'],
                ['code' => 'KW', 'name' => 'Kuwait', 'flag' => '🇰🇼'],
                ['code' => 'BH', 'name' => 'Bahrain', 'flag' => '🇧🇭'],
                ['code' => 'OM', 'name' => 'Oman', 'flag' => '🇴🇲'],
                ['code' => 'CA', 'name' => 'Canada', 'flag' => '🇨🇦'],
                ['code' => 'AT', 'name' => 'Austria', 'flag' => '🇦🇹'],
            ];

            return Inertia::render('LandingPage/Settings', [
                'settings' => $settings ?: [
                    'company_name' => '',
                    'contact_email' => '',
                    'contact_phone' => '',
                    'contact_address' => '',
                    'default_country' => 'US',
                    'config_sections' => [
                        'sections' => [],
                        'section_visibility' => [
                            'header' => true,
                            'hero' => true,
                            'stats' => true,
                            'features' => true,
                            'modules' => true,
                            'benefits' => true,
                            'gallery' => true,
                            'cta' => true,
                            'footer' => true
                        ],
                        'section_order' => ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer']
                    ]
                ],
                'customPages' => $customPages,
                'availableCountries' => $availableCountries
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreLandingPageRequest $request)
    {
        if(Auth::user()->can('edit-landing-page')){
            $validated = $request->validated();

            // Handle image paths - store only filename
            if (isset($validated['config_sections']['sections'])) {
                $this->processImagePaths($validated['config_sections']['sections']);
            }

            LandingPageSetting::updateOrCreate(['id' => 1], $validated);

            return back()->with('success', __('Settings saved successfully'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    private function processImagePaths(&$sections)
    {
        foreach ($sections as $sectionKey => &$sectionData) {
            if (is_array($sectionData)) {
                // Handle single images (hero, cta)
                if (isset($sectionData['image'])) {
                    $sectionData['image'] = $this->processImagePath($sectionData['image']);
                }
                
                // Handle gallery images array
                if (isset($sectionData['images']) && is_array($sectionData['images'])) {
                    $sectionData['images'] = array_map([$this, 'processImagePath'], $sectionData['images']);
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
}


