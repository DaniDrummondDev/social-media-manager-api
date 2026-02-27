<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class BoostMetricsOutput
{
    /**
     * @param  array<array{period: string, impressions: int, reach: int, clicks: int, spend_cents: int, spend_currency: string, conversions: int, ctr: float, cpc: ?float, cpm: ?float, cost_per_conversion: ?float, captured_at: string}>  $snapshots
     * @param  array{impressions: int, reach: int, clicks: int, spend_cents: int, conversions: int, ctr: float, cpc: ?float, cpm: ?float}  $summary
     */
    public function __construct(
        public string $boostId,
        public array $snapshots,
        public array $summary,
    ) {}
}
