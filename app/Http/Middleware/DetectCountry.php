<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CountryDetectionService;

class DetectCountry
{
    protected CountryDetectionService $detector;

    public function __construct(CountryDetectionService $detector)
    {
        $this->detector = $detector;
    }

    public function handle(Request $request, Closure $next)
    {
        $countryCode = $this->detector->detect($request);
        
        // Store in session for later use
        $request->session()->put('country_code', $countryCode);
        
        // Share with all views
        app()->singleton('country_code', function () use ($countryCode) {
            return $countryCode;
        });
        
        return $next($request);
    }
}
