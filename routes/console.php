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

// Check overdue client invoices — daily at 06:00
Schedule::job(new \App\Infrastructure\ClientFinance\Jobs\CheckOverdueInvoicesJob)->dailyAt('06:00');

// Generate monthly client invoices — 1st of month at 07:00
Schedule::job(new \App\Infrastructure\ClientFinance\Jobs\GenerateMonthlyInvoicesJob)->monthlyOn(1, '07:00');

// Send invoice reminders — daily at 08:00
Schedule::job(new \App\Infrastructure\ClientFinance\Jobs\SendInvoiceReminderJob)->dailyAt('08:00');

// Fetch mentions from social networks — every 15 minutes
Schedule::job(new \App\Infrastructure\SocialListening\Jobs\FetchMentionsJob)->everyFifteenMinutes();

// Evaluate listening alerts — every 5 minutes
Schedule::job(new \App\Infrastructure\SocialListening\Jobs\EvaluateListeningAlertsJob)->everyFiveMinutes();

// Dispatch daily listening reports — daily at 06:00 UTC
Schedule::job(new \App\Infrastructure\SocialListening\Jobs\DispatchDailyListeningReportsJob)->dailyAt('06:00');

// Cleanup old mentions — daily at 07:00
Schedule::job(new \App\Infrastructure\SocialListening\Jobs\CleanupOldMentionsJob)->dailyAt('07:00');

// Recalculate prompt performance scores — weekly on Sundays at 03:00
Schedule::job(new \App\Infrastructure\ContentAI\Jobs\CalculatePromptPerformanceJob)->weeklyOn(0, '03:00');

// Cleanup expired learning data (style profiles, old validations) — weekly on Sundays at 04:00
Schedule::job(new \App\Infrastructure\AIIntelligence\Jobs\CleanupExpiredLearningDataJob)->weeklyOn(0, '04:00');

// AI Intelligence: Weekly recalculation of best posting times
// TODO: Implement RecalculateAllBestTimesJob that iterates organizations and dispatches
// CalculateBestPostingTimesJob for each. Schedule weekly on Mondays at 04:00 UTC.

// AI Intelligence: Weekly content profile recalculation
// TODO: Implement RecalculateContentProfilesJob that iterates organizations and dispatches
// GenerateContentProfileJob for each. Schedule weekly on Tuesdays at 04:00 UTC.

// AI Intelligence: Weekly audience insights refresh
// TODO: Implement RefreshAllAudienceInsightsJob that iterates organizations and dispatches
// RefreshAudienceInsightsJob for each. Schedule weekly on Wednesdays at 05:00 UTC.

// AI Intelligence: Monthly content gap analysis
// TODO: Implement GenerateAllContentGapAnalysesJob that iterates organizations with competitor
// queries and dispatches GenerateContentGapAnalysisJob for each. Schedule monthly on 15th at 05:00 UTC.

// Paid Advertising: Sync ad status — every 30 minutes
Schedule::job(new \App\Infrastructure\PaidAdvertising\Jobs\SyncAdStatusJob)->everyThirtyMinutes();

// Paid Advertising: Sync ad metrics — hourly
Schedule::job(new \App\Infrastructure\PaidAdvertising\Jobs\SyncAdMetricsJob)->hourly();

// Paid Advertising: Refresh ad account tokens — twice daily at 02:00 and 14:00
Schedule::job(new \App\Infrastructure\PaidAdvertising\Jobs\RefreshAdAccountTokenJob)->twiceDaily(2, 14);
