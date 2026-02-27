<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Listeners;

use App\Domain\AIIntelligence\Events\AdPerformanceAggregated;
use App\Infrastructure\AIIntelligence\Jobs\EnrichAIContextFromAdsJob;

final class EnrichAIContextOnAdPerformanceAggregated
{
    public function handle(AdPerformanceAggregated $event): void
    {
        EnrichAIContextFromAdsJob::dispatch(
            organizationId: $event->organizationId,
            userId: $event->userId,
        );
    }
}
