<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Listeners;

use App\Domain\Publishing\Events\PostPublished;
use App\Infrastructure\AIIntelligence\Jobs\ValidatePredictionJob;

/**
 * Schedules prediction validation 24 hours after a post is published.
 *
 * This delay allows time for engagement metrics to accumulate
 * before comparing predicted vs actual performance.
 */
final class SchedulePredictionValidation
{
    private const VALIDATION_DELAY_HOURS = 24;

    public function handle(PostPublished $event): void
    {
        ValidatePredictionJob::dispatch(
            organizationId: $event->organizationId,
            contentId: $event->contentId,
            scheduledPostId: $event->aggregateId,
            validationType: 'scheduled_24h',
        )->delay(now()->addHours(self::VALIDATION_DELAY_HOURS));
    }
}
