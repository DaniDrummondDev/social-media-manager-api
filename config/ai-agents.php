<?php

declare(strict_types=1);

return [
    'base_url' => env('AI_AGENTS_BASE_URL', 'http://ai-agents:8000'),
    'internal_secret' => env('AI_AGENTS_INTERNAL_SECRET'),
    'poll_timeout' => (int) env('AI_AGENTS_POLL_TIMEOUT', 120),
    'poll_interval_ms' => (int) env('AI_AGENTS_POLL_INTERVAL_MS', 500),

    'circuit_breaker' => [
        'failure_threshold' => 3,
        'open_timeout' => 120,
    ],

    'plan_access' => [
        'content_creation' => ['professional', 'agency'],
        'visual_adaptation' => ['professional', 'agency'],
        'content_dna' => ['agency'],
        'social_listening' => ['agency'],
    ],

    'plan_daily_limits' => [
        'professional' => [
            'content_creation' => 3,
            'visual_adaptation' => 5,
        ],
        'agency' => [
            'content_creation' => -1,
            'visual_adaptation' => -1,
            'content_dna' => -1,
            'social_listening' => -1,
        ],
    ],
];
