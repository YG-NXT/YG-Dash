<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'hmrc' => [
        'base_url' => env('HMRC_BASE_URL', 'https://api.service.hmrc.gov.uk'),
        'client_id' => env('HMRC_CLIENT_ID'),
        'client_secret' => env('HMRC_CLIENT_SECRET'),
        'redirect_uri' => env('HMRC_REDIRECT_URI'),
    ],

    'companies_house' => [
        'base_url' => env('COMPANIES_HOUSE_BASE_URL', 'https://api.company-information.service.gov.uk'),
        'api_key' => env('COMPANIES_HOUSE_API_KEY'),
    ],

    'nhs' => [
        'base_url' => env('NHS_BASE_URL', 'https://api.nhs.uk'),
        'api_key' => env('NHS_API_KEY'),
    ],

    'cqc' => [
        'base_url' => env('CQC_BASE_URL', 'https://api.cqc.org.uk/public/v1'),
        'api_key' => env('CQC_API_KEY'),
    ],

    'fsa' => [
        'base_url' => env('FSA_BASE_URL', 'https://ratings.food.gov.uk/open-data'),
    ],

];
