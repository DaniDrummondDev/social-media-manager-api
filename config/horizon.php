<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    */

    'waits' => [
        'redis:high' => 15,
        'redis:publishing' => 30,
        'redis:billing' => 30,
        'redis:default' => 60,
        'redis:analytics' => 120,
        'redis:engagement' => 120,
        'redis:notifications' => 60,
        'redis:webhooks' => 30,
        'redis:social-listening' => 180,
        'redis:content-ai' => 180,
        'redis:ai-intelligence' => 180,
        'redis:admin' => 60,
        'redis:client-finance' => 120,
        'redis:low' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    */

    'silenced' => [
        // Jobs that run frequently and don't need individual tracking
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */

    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Production environment configuration optimized for:
    | - High throughput for time-sensitive jobs (publishing, billing)
    | - AI/ML workloads with longer timeouts
    | - Analytics and engagement processing
    | - Background maintenance tasks
    |
    */

    'defaults' => [
        'supervisor-high-priority' => [
            'connection' => 'redis',
            'queue' => ['high', 'publishing', 'billing'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 4,
            'minProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 1000,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications', 'webhooks'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 3,
            'minProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 1000,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 90,
            'nice' => 0,
        ],
        'supervisor-ai' => [
            'connection' => 'redis',
            'queue' => ['content-ai', 'ai-intelligence'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 4,
            'minProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 500,
            'memory' => 256,
            'tries' => 2,
            'timeout' => 300,
            'nice' => 5,
        ],
        'supervisor-analytics' => [
            'connection' => 'redis',
            'queue' => ['analytics', 'engagement', 'social-listening'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 3,
            'minProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 500,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 180,
            'nice' => 5,
        ],
        'supervisor-background' => [
            'connection' => 'redis',
            'queue' => ['admin', 'client-finance', 'low'],
            'balance' => 'simple',
            'maxProcesses' => 2,
            'minProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 500,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 300,
            'nice' => 10,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-high-priority' => [
                'connection' => 'redis',
                'queue' => ['high', 'publishing', 'billing'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 6,
                'minProcesses' => 2,
                'maxTime' => 0,
                'maxJobs' => 1000,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 120,
                'nice' => 0,
            ],
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default', 'notifications', 'webhooks'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 4,
                'minProcesses' => 2,
                'maxTime' => 0,
                'maxJobs' => 1000,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 90,
                'nice' => 0,
            ],
            'supervisor-ai' => [
                'connection' => 'redis',
                'queue' => ['content-ai', 'ai-intelligence'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 6,
                'minProcesses' => 2,
                'maxTime' => 0,
                'maxJobs' => 500,
                'memory' => 256,
                'tries' => 2,
                'timeout' => 300,
                'nice' => 5,
            ],
            'supervisor-analytics' => [
                'connection' => 'redis',
                'queue' => ['analytics', 'engagement', 'social-listening'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 4,
                'minProcesses' => 2,
                'maxTime' => 0,
                'maxJobs' => 500,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 180,
                'nice' => 5,
            ],
            'supervisor-background' => [
                'connection' => 'redis',
                'queue' => ['admin', 'client-finance', 'low'],
                'balance' => 'simple',
                'maxProcesses' => 2,
                'minProcesses' => 1,
                'maxTime' => 0,
                'maxJobs' => 500,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 300,
                'nice' => 10,
            ],
        ],

        'staging' => [
            'supervisor-high-priority' => [
                'connection' => 'redis',
                'queue' => ['high', 'publishing', 'billing'],
                'balance' => 'auto',
                'maxProcesses' => 2,
                'minProcesses' => 1,
                'tries' => 3,
                'timeout' => 120,
            ],
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default', 'notifications', 'webhooks'],
                'balance' => 'auto',
                'maxProcesses' => 2,
                'minProcesses' => 1,
                'tries' => 3,
                'timeout' => 90,
            ],
            'supervisor-ai' => [
                'connection' => 'redis',
                'queue' => ['content-ai', 'ai-intelligence'],
                'balance' => 'auto',
                'maxProcesses' => 2,
                'minProcesses' => 1,
                'memory' => 256,
                'tries' => 2,
                'timeout' => 300,
            ],
            'supervisor-analytics' => [
                'connection' => 'redis',
                'queue' => ['analytics', 'engagement', 'social-listening'],
                'balance' => 'auto',
                'maxProcesses' => 2,
                'minProcesses' => 1,
                'tries' => 3,
                'timeout' => 180,
            ],
            'supervisor-background' => [
                'connection' => 'redis',
                'queue' => ['admin', 'client-finance', 'low'],
                'balance' => 'simple',
                'maxProcesses' => 1,
                'tries' => 3,
                'timeout' => 300,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => [
                    'high',
                    'publishing',
                    'billing',
                    'default',
                    'notifications',
                    'webhooks',
                    'content-ai',
                    'ai-intelligence',
                    'analytics',
                    'engagement',
                    'social-listening',
                    'admin',
                    'client-finance',
                    'low',
                ],
                'balance' => 'auto',
                'maxProcesses' => 3,
                'minProcesses' => 1,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 300,
            ],
        ],
    ],
];
