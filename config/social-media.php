<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Providers
    |--------------------------------------------------------------------------
    |
    | List of social media providers supported by the platform.
    | Each provider must implement the adapter interfaces defined in
    | App\Domain\SocialAccount\Contracts.
    |
    */

    'providers' => ['instagram', 'tiktok', 'youtube'],

    /*
    |--------------------------------------------------------------------------
    | Provider Configuration
    |--------------------------------------------------------------------------
    |
    | OAuth credentials, scopes, rate limits, content limits and token
    | refresh settings for each social media provider.
    |
    */

    'instagram' => [
        'client_id' => env('INSTAGRAM_CLIENT_ID'),
        'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
        'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),

        'scopes' => [
            'instagram_basic',
            'instagram_content_publish',
            'instagram_manage_comments',
            'instagram_manage_insights',
        ],

        'token' => [
            'access_ttl_days' => 60,
            'refresh_before_days' => 7,
        ],

        'rate_limit' => [
            'calls_per_hour' => 200,
        ],

        'publishing' => [
            'daily_limit' => 25,
        ],

        'content_limits' => [
            'description_max' => 2200,
            'hashtags_max' => 30,
            'carousel_max_items' => 10,
        ],

        'media' => [
            'image_formats' => ['jpg', 'jpeg', 'png'],
            'video_formats' => ['mp4'],
            'min_video_duration' => 3,
            'max_video_duration' => 5400, // 90 minutes in seconds
            'max_file_size' => 1073741824, // 1 GB in bytes
        ],
    ],

    'tiktok' => [
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect_uri' => env('TIKTOK_REDIRECT_URI'),

        'scopes' => [
            'video.publish',
            'video.list',
            'comment.list',
            'comment.list.manage',
            'user.info.basic',
        ],

        'token' => [
            'access_ttl_hours' => 24,
            'refresh_before_days' => 7,
        ],

        'rate_limit' => [
            'strategy' => 'token_bucket',
        ],

        'publishing' => [
            'daily_limit' => null, // Varies by app approval
        ],

        'content_limits' => [
            'title_max' => 150,
            'description_max' => 4000,
            'hashtags_recommended' => 5,
        ],

        'media' => [
            'video_formats' => ['mp4'],
            'min_video_duration' => 1,
            'max_video_duration' => 600, // 10 minutes in seconds
            'max_file_size' => 4294967296, // 4 GB in bytes
        ],
    ],

    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI'),

        'scopes' => [
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube.force-ssl',
        ],

        'token' => [
            'access_ttl_minutes' => 60,
            'refresh_before_minutes' => 10,
        ],

        'rate_limit' => [
            'daily_quota_units' => 10000,
        ],

        'publishing' => [
            'daily_limit' => 6, // Quota-based
        ],

        'content_limits' => [
            'title_max' => 100,
            'description_max' => 5000,
            'tags_max' => 15,
        ],

        'media' => [
            'video_formats' => ['mp4'],
            'thumbnail_formats' => ['jpg', 'jpeg', 'png'],
            'min_video_duration' => 1,
            'max_video_duration' => 43200, // 12 hours in seconds
            'max_file_size' => 274877906944, // 256 GB in bytes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Encryption
    |--------------------------------------------------------------------------
    |
    | Social media tokens are encrypted with AES-256-GCM using a dedicated key,
    | separate from Laravel's APP_KEY. Tokens are decrypted only at point of
    | use (just-in-time) and never cached or logged.
    |
    */

    'encryption' => [
        'key' => env('SOCIAL_TOKEN_ENCRYPTION_KEY'),
        'cipher' => 'aes-256-gcm',
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth State
    |--------------------------------------------------------------------------
    |
    | State token settings for OAuth CSRF protection.
    | Stored in Redis with a short TTL and single-use.
    |
    */

    'oauth' => [
        'state_ttl' => 600, // 10 minutes in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Per-provider circuit breaker to prevent cascading failures.
    | After reaching the failure threshold, the circuit opens and no requests
    | are sent for the open_timeout duration.
    |
    */

    'circuit_breaker' => [
        'failure_threshold' => (int) env('CIRCUIT_BREAKER_THRESHOLD', 5),
        'open_timeout' => (int) env('CIRCUIT_BREAKER_TIMEOUT', 300), // 5 minutes
        'half_open_max_attempts' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Strategy
    |--------------------------------------------------------------------------
    |
    | Retry configuration for transient failures during publishing.
    | Only retries on HTTP 429, 500, 502, 503, 504 and timeouts.
    | Never retries on 400, 401, 403, 404 (permanent errors).
    |
    */

    'retry' => [
        'max_attempts' => 3,
        'backoff' => [60, 300, 900], // 1min, 5min, 15min
        'retryable_status_codes' => [429, 500, 502, 503, 504],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Sync
    |--------------------------------------------------------------------------
    |
    | Intervals for syncing metrics from social media providers.
    |
    */

    'analytics' => [
        'recent_content_hours' => 48,
        'recent_interval_minutes' => 60,
        'standard_interval_minutes' => 360, // 6 hours
        'account_metrics_interval_minutes' => 360, // 6 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Engagement
    |--------------------------------------------------------------------------
    |
    | Comment polling and automation response settings.
    |
    */

    'engagement' => [
        'comment_poll_recent_minutes' => 30,
        'comment_poll_recent_days' => 30,
        'comment_poll_old_hours' => 24,

        'automation_min_delay' => 30, // seconds
        'automation_max_delay' => 3600, // seconds
        'automation_default_delay' => 120, // seconds
        'automation_daily_limit_default' => 100,
        'automation_daily_limit_min' => 10,
        'automation_daily_limit_max' => 1000,
    ],

];
