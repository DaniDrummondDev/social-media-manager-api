<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\Entities\ContentMetric;
use App\Domain\Analytics\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;

interface ContentMetricRepositoryInterface
{
    public function upsert(ContentMetric $metric): void;

    public function findByContentAndAccount(Uuid $contentId, Uuid $socialAccountId): ?ContentMetric;

    /**
     * @return array<ContentMetric>
     */
    public function findTopByEngagement(Uuid $organizationId, MetricPeriod $period, int $limit = 5): array;

    /**
     * @return array<string, mixed>
     */
    public function getAggregatedMetrics(Uuid $organizationId, MetricPeriod $period): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDailyTrend(Uuid $organizationId, MetricPeriod $period): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getByNetworkSummary(Uuid $organizationId, MetricPeriod $period): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBestPostingTimes(Uuid $organizationId, ?string $provider = null, int $minPosts = 3): array;
}
