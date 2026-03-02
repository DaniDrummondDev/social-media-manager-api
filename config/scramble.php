<?php

declare(strict_types=1);

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
    |--------------------------------------------------------------------------
    | API Path
    |--------------------------------------------------------------------------
    */
    'api_path' => 'api/v1',

    'api_domain' => null,

    'export_path' => 'api.json',

    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    */
    'info' => [
        'version' => '1.0.0',
        'description' => 'API SaaS para agendamento e gestão de conteúdo em redes sociais.',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'title' => 'Social Media Manager API',
        'theme' => 'light',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Configuration
    |--------------------------------------------------------------------------
    |
    | When null, server URL is auto-generated from api_path + api_domain.
    | Format: 'Label' => 'path-or-url'
    |
    */
    'servers' => null,

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
