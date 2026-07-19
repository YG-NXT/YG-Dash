<?php

namespace App\Http\Controllers;

use App\Classes\CountryPackageManager;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CountryPackageController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('manage-country-packages')) {
            return back()->with('error', __('Permission denied'));
        }

        $countryManager = app(CountryPackageManager::class);
        $packages = $countryManager->discover();

        $packagesWithStats = [];
        foreach ($packages as $package) {
            $countryCode = $package['config']['country_code'] ?? null;
            $packageName = $package['name'];
            
            $companyCount = 0;
            if ($countryCode) {
                $companyCount = User::where('type', 'company')
                    ->where('country_code', $countryCode)
                    ->count();
            }

            $packagesWithStats[] = [
                'name' => $packageName,
                'alias' => $package['config']['alias'] ?? $packageName,
                'country_code' => $countryCode,
                'description' => $package['config']['description'] ?? '',
                'version' => $package['config']['version'] ?? '1.0.0',
                'package_name' => $package['config']['package_name'] ?? strtolower($packageName),
                'is_active' => $countryManager->isActive($packageName),
                'company_count' => $companyCount,
                'flag' => $this->getFlag($countryCode),
            ];
        }

        usort($packagesWithStats, function ($a, $b) {
            return $a['alias'] <=> $b['alias'];
        });

        return Inertia::render('CountryPackages/Index', [
            'packages' => $packagesWithStats,
            'totalCountries' => count($packagesWithStats),
            'activeCountries' => count(array_filter($packagesWithStats, fn($p) => $p['is_active'])),
            'totalCompanies' => array_sum(array_column($packagesWithStats, 'company_count')),
        ]);
    }

    public function toggle(Request $request, string $packageName)
    {
        if (!Auth::user()->can('manage-country-packages')) {
            return back()->with('error', __('Permission denied'));
        }

        $countryManager = app(CountryPackageManager::class);
        $packages = $countryManager->discover();
        $package = collect($packages)->firstWhere('name', $packageName);

        if (!$package) {
            return back()->with('error', __('Country package not found'));
        }

        $countryCode = $package['config']['country_code'] ?? null;
        if (!$countryCode) {
            return back()->with('error', __('Invalid country package'));
        }

        if ($countryManager->isActive($packageName)) {
            return back()->with('success', __('Country package :package is already active', ['package' => $package['config']['alias']]));
        }

        try {
            $this->activatePackage($packageName, $countryCode);

            return back()->with('success', __('Country package :package activated successfully', ['package' => $package['config']['alias']]));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to activate country package: :error', ['error' => $e->getMessage()]));
        }
    }

    public function stats(Request $request, string $countryCode)
    {
        if (!Auth::user()->can('manage-country-packages')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $companies = User::where('type', 'company')
            ->where('country_code', $countryCode)
            ->get(['id', 'name', 'email', 'created_at', 'active_plan']);

        $stats = [
            'total_companies' => $companies->count(),
            'active_companies' => $companies->filter(fn($c) => $c->active_plan)->count(),
            'companies' => $companies->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'created_at' => $c->created_at->format('Y-m-d'),
                'active_plan' => $c->active_plan ? Plan::find($c->active_plan)->name ?? 'Active' : 'Inactive',
            ]),
        ];

        return response()->json($stats);
    }

    protected function activatePackage(string $packageName, string $countryCode): void
    {
        $packagePath = base_path("packages/workdo/{$packageName}");

        if (!is_dir($packagePath)) {
            throw new \Exception("Package directory not found: {$packageName}");
        }

        $migrationsPath = "{$packagePath}/src/Database/Migrations";
        if (is_dir($migrationsPath)) {
            try {
                \Artisan::call('migrate', [
                    '--path' => "packages/workdo/{$packageName}/src/Database/Migrations",
                    '--force' => true,
                ]);
            } catch (\Exception $e) {
                \Log::warning("Migration failed for {$packageName}: " . $e->getMessage());
            }
        }

        try {
            \Artisan::call('package:seed', [$packageName]);
        } catch (\Exception $e) {
            \Log::warning("Seeding failed for {$packageName}: " . $e->getMessage());
        }

        app(CountryPackageManager::class)->loadActive();
    }

    protected function getFlag(?string $countryCode): string
    {
        if (!$countryCode) return '🌐';

        $flags = [
            'GB' => '🇬🇧',
            'US' => '🇺🇸',
            'AE' => '🇦🇪',
            'AU' => '🇦🇺',
            'DE' => '🇩🇪',
            'FR' => '🇫🇷',
            'IN' => '🇮🇳',
            'JP' => '🇯🇵',
            'SA' => '🇸🇦',
            'QA' => '🇶🇦',
            'KW' => '🇰🇼',
            'BH' => '🇧🇭',
            'OM' => '🇴🇲',
            'CA' => '🇨🇦',
            'AT' => '🇦🇹',
        ];

        return $flags[strtoupper($countryCode)] ?? '🌐';
    }
}

