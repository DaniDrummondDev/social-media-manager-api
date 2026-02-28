<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CRM Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for each CRM provider supported by the platform.
    | Each provider must implement CrmConnectorInterface.
    |
    */

    'salesforce' => [
        'client_id' => env('SALESFORCE_CLIENT_ID'),
        'client_secret' => env('SALESFORCE_CLIENT_SECRET'),
        'redirect_uri' => env('SALESFORCE_REDIRECT_URI'),
        'instance_url' => env('SALESFORCE_INSTANCE_URL', 'https://login.salesforce.com'),
        'api_version' => env('SALESFORCE_API_VERSION', 'v58.0'),

        'scopes' => [
            'api',
            'refresh_token',
            'offline_access',
        ],

        'rate_limit' => [
            'daily_requests' => 15000,
        ],
    ],

    'activecampaign' => [
        'api_url' => env('ACTIVECAMPAIGN_API_URL'),

        'rate_limit' => [
            'requests_per_second' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth State
    |--------------------------------------------------------------------------
    |
    | State token settings for CRM OAuth CSRF protection.
    | Stored in Redis with a short TTL and single-use.
    |
    */

    'oauth' => [
        'state_ttl' => 600, // 10 minutes in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Encryption
    |--------------------------------------------------------------------------
    |
    | AES-256-GCM encryption key for CRM tokens at rest.
    | Must be 32 bytes, base64 encoded.
    |
    */

    'encryption' => [
        'key' => env('CRM_TOKEN_ENCRYPTION_KEY'),
    ],

];
