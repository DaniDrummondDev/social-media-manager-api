<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Listeners;

use App\Domain\Publishing\Events\PostPublished;
use App\Infrastructure\Analytics\Jobs\SyncPostMetricsJob;

final class ScheduleMetricsSyncOnPostPublished
{
    public function handle(PostPublished $event): void
    {
        $scheduledPostId = $event->aggregateId;

        SyncPostMetricsJob::dispatch($scheduledPostId)
            ->onQueue('analytics')
            ->delay(now()->addHours(24));

        SyncPostMetricsJob::dispatch($scheduledPostId)
            ->onQueue('analytics')
            ->delay(now()->addHours(48));

        SyncPostMetricsJob::dispatch($scheduledPostId)
            ->onQueue('analytics')
            ->delay(now()->addDays(7));
    }
}
