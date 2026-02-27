<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Repositories;

use App\Domain\PaidAdvertising\Entities\AdMetricSnapshot;
use App\Domain\PaidAdvertising\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface AdMetricSnapshotRepositoryInterface
{
    public function create(AdMetricSnapshot $snapshot): void;

    /**
     * @param  array<AdMetricSnapshot>  $snapshots
     */
    public function createBatch(array $snapshots): void;

    /**
     * @return array<AdMetricSnapshot>
     */
    public function findByBoostId(Uuid $boostId): array;

    /**
     * @return array<AdMetricSnapshot>
     */
    public function findByBoostIdAndPeriod(Uuid $boostId, MetricPeriod $period): array;

    /**
     * @return array{total_spend_cents: int, currency: string}
     */
    public function getTotalSpend(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * @return array<array{date: string, spend_cents: int, impressions: int, clicks: int, conversions: int}>
     */
    public function getSpendingHistory(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to): array;

    public function getLatestByBoostId(Uuid $boostId): ?AdMetricSnapshot;
}
