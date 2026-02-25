<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\Entities\AccountMetric;
use App\Domain\Analytics\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;

interface AccountMetricRepositoryInterface
{
    public function upsert(AccountMetric $metric): void;

    public function findBySocialAccountId(Uuid $socialAccountId): ?AccountMetric;

    public function getLatest(Uuid $socialAccountId): ?AccountMetric;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFollowersTrend(Uuid $socialAccountId, MetricPeriod $period): array;

    /**
     * @return array<string, mixed>
     */
    public function getAccountSummary(Uuid $socialAccountId, MetricPeriod $period): array;
}
