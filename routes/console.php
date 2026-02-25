<?php

use App\Infrastructure\Analytics\Jobs\SyncAccountMetricsJob;
use App\Infrastructure\Engagement\Jobs\CaptureCommentsJob;
use App\Infrastructure\Engagement\Jobs\RetryWebhookDeliveriesJob;
use App\Infrastructure\Media\Jobs\CleanupAbandonedUploadsJob;
use App\Infrastructure\Publishing\Jobs\DispatchScheduledPostsJob;
use App\Infrastructure\SocialAccount\Jobs\CheckAccountsHealthJob;
use App\Infrastructure\SocialAccount\Jobs\RefreshExpiringTokensJob;
use Illuminate\Support\Facades\Schedule;

// Token refresh — every 12 hours
Schedule::job(new RefreshExpiringTokensJob)->twiceDaily(0, 12);

// Account health check — every 6 hours
Schedule::job(new CheckAccountsHealthJob)->everySixHours();

// Cleanup abandoned uploads — every hour
Schedule::job(new CleanupAbandonedUploadsJob)->hourly();

// Dispatch scheduled posts — every minute
Schedule::job(new DispatchScheduledPostsJob)->everyMinute();

// Sync account metrics — every 6 hours
Schedule::job(new SyncAccountMetricsJob)->everySixHours();

// Capture comments from social networks — every 30 minutes
Schedule::job(new CaptureCommentsJob)->everyThirtyMinutes();

// Retry failed webhook deliveries — every 5 minutes
Schedule::job(new RetryWebhookDeliveriesJob)->everyFiveMinutes();

// Check expired subscriptions — daily at 03:00
Schedule::job(new \App\Infrastructure\Billing\Jobs\CheckExpiredSubscriptionsJob)->dailyAt('03:00');

// Sync usage records — daily at 04:00
Schedule::job(new \App\Infrastructure\Billing\Jobs\SyncUsageRecordsJob)->dailyAt('04:00');

// Cleanup suspended organizations — daily at 05:00
Schedule::job(new \App\Infrastructure\PlatformAdmin\Jobs\CleanupSuspendedOrgsJob)->dailyAt('05:00');

// Compute dashboard metrics — every 5 minutes
Schedule::job(new \App\Infrastructure\PlatformAdmin\Jobs\ComputeDashboardMetricsJob)->everyFiveMinutes();
