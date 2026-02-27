<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ad Platform Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for each ad platform supported by the system.
    | Each provider must implement AdPlatformInterface.
    |
    */

    'meta' => [
        'app_id' => env('META_ADS_APP_ID'),
        'app_secret' => env('META_ADS_APP_SECRET'),
        'redirect_uri' => env('META_ADS_REDIRECT_URI'),

        'scopes' => [
            'ads_management',
            'ads_read',
            'business_management',
        ],
    ],

    'tiktok' => [
        'app_id' => env('TIKTOK_ADS_APP_ID'),
        'app_secret' => env('TIKTOK_ADS_APP_SECRET'),
        'redirect_uri' => env('TIKTOK_ADS_REDIRECT_URI'),

        'scopes' => [
            'ad.management',
            'ad.read',
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'redirect_uri' => env('GOOGLE_ADS_REDIRECT_URI'),

        'scopes' => [
            'https://www.googleapis.com/auth/adwords',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Encryption
    |--------------------------------------------------------------------------
    |
    | Dedicated AES-256-GCM encryption key for ad account tokens.
    | Must be 32 bytes, base64-encoded.
    |
    */

    'encryption' => [
        'key' => env('AD_TOKEN_ENCRYPTION_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth State
    |--------------------------------------------------------------------------
    |
    | State token settings for Ad OAuth CSRF protection.
    | Stored in cache with a short TTL and single-use.
    |
    */

    'oauth' => [
        'state_ttl' => 600, // 10 minutes in seconds
    ],

];
