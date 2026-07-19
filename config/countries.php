<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Countries
    |--------------------------------------------------------------------------
    |
    | This list is dynamically populated from installed Country packages.
    | Each country package (CountryGB, CountryUS, etc.) registers itself
    | automatically when discovered by the CountryPackageManager.
    |
    | Format:
    |   'GB' => [
    |       'name' => 'United Kingdom',
    |       'local_name' => 'UK',
    |       'currency' => 'GBP',
    |       'currency_symbol' => '£',
    |       'locale' => 'en-GB',
    |       'timezone' => 'Europe/London',
    |       'phone_code' => '+44',
    |       'postcode_format' => '[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}',
    |       'vat_label' => 'VAT Number',
    |       'tax_system' => 'vat',
    |       'flag' => '🇬🇧',
    |   ],
    */

    'default' => 'US',

    'cache_ttl' => 3600, // seconds

    /*
    |--------------------------------------------------------------------------
    | Currency Conversion
    |--------------------------------------------------------------------------
    */
    'currency_conversion' => [
        'enabled' => true,
        'provider' => 'openexchangerates', // or 'exchangerate-api', 'fixer', 'currencylayer'
        'api_key' => env('CURRENCY_API_KEY'),
        'base_currency' => 'USD',
        'update_interval' => 3600, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Geolocation
    |--------------------------------------------------------------------------
    */
    'geolocation' => [
        'provider' => 'ip-api', // or 'maxmind', 'ipstack'
        'api_key' => env('GEOLOCATION_API_KEY'),
        'timeout' => 5, // seconds
        'cache_ttl' => 86400, // 24 hours
    ],
];
