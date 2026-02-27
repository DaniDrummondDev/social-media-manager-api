<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Listeners;

use App\Domain\PaidAdvertising\Events\AdMetricsSynced;

final class ScheduleAdPerformanceAggregation
{
    public function handle(AdMetricsSynced $event): void
    {
        // TODO: Sprint 18 — Dispatch AggregateAdPerformanceJob
    }
}
