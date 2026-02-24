<?php

use App\Infrastructure\Media\Jobs\CleanupAbandonedUploadsJob;
use App\Infrastructure\SocialAccount\Jobs\CheckAccountsHealthJob;
use App\Infrastructure\SocialAccount\Jobs\RefreshExpiringTokensJob;
use Illuminate\Support\Facades\Schedule;

// Token refresh — every 12 hours
Schedule::job(new RefreshExpiringTokensJob)->twiceDaily(0, 12);

// Account health check — every 6 hours
Schedule::job(new CheckAccountsHealthJob)->everySixHours();

// Cleanup abandoned uploads — every hour
Schedule::job(new CleanupAbandonedUploadsJob)->hourly();
