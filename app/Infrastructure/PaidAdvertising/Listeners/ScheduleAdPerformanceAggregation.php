<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Listeners;

use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\PaidAdvertising\Events\AdMetricsSynced;
use App\Infrastructure\AIIntelligence\Jobs\AggregateAdPerformanceJob;

final class ScheduleAdPerformanceAggregation
{
    public function handle(AdMetricsSynced $event): void
    {
        foreach (AdInsightType::cases() as $type) {
            AggregateAdPerformanceJob::dispatch(
                organizationId: $event->organizationId,
                userId: $event->userId,
                adInsightType: $type->value,
            );
        }
    }
}
