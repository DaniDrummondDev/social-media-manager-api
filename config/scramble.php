<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    */
    'info' => [
        'title' => 'Social Media Manager API',
        'version' => '1.0.0',
        'description' => 'API SaaS para agendamento e gestão de conteúdo em redes sociais.',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Path
    |--------------------------------------------------------------------------
    |
    | The path prefix for API routes to document.
    |
    */
    'api_path' => 'api/v1',

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Documentation
    |--------------------------------------------------------------------------
    |
    | Disable in production for security.
    |
    */
    'enabled' => env('SCRAMBLE_ENABLED', env('APP_ENV') !== 'production'),

    /*
    |--------------------------------------------------------------------------
    | Server Configuration
    |--------------------------------------------------------------------------
    */
    'servers' => [
        [
            'url' => env('APP_URL').'/api/v1',
            'description' => env('APP_ENV', 'local'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    */
    'security' => [
        'bearer' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Filter
    |--------------------------------------------------------------------------
    |
    | Filter which routes to include in the documentation.
    |
    */
    'routes' => function (\Illuminate\Routing\Route $route): bool {
        $uri = $route->uri();

        // Exclude internal routes
        if (str_contains($uri, 'internal/')) {
            return false;
        }

        return str_starts_with($uri, 'api/v1');
    },

    /*
    |--------------------------------------------------------------------------
    | UI and JSON Paths
    |--------------------------------------------------------------------------
    */
    'ui_path' => 'docs/api',
    'json_path' => 'docs/api.json',

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    */
    'theme' => 'light',
];
