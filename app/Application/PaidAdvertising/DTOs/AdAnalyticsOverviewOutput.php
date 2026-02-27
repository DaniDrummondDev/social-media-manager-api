<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class AdAnalyticsOverviewOutput
{
    public function __construct(
        public int $totalSpendCents,
        public string $currency,
        public int $totalImpressions,
        public int $totalClicks,
        public int $totalConversions,
        public float $avgCtr,
        public ?float $avgCpc,
        public int $activeBoosts,
        public int $completedBoosts,
    ) {}
}
