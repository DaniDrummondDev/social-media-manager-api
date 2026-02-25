<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\DTOs\GetOverviewInput;
use App\Application\Analytics\DTOs\GetOverviewOutput;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;
use App\Domain\Analytics\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GetOverviewUseCase
{
    public function __construct(
        private readonly ContentMetricRepositoryInterface $contentMetricRepository,
    ) {}

    public function execute(GetOverviewInput $input): GetOverviewOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $period = $this->resolvePeriod($input);
        $previousPeriod = $period->previousPeriod();

        $summary = $this->contentMetricRepository->getAggregatedMetrics($organizationId, $period);
        $comparison = $this->contentMetricRepository->getAggregatedMetrics($organizationId, $previousPeriod);
        $byNetwork = $this->contentMetricRepository->getByNetworkSummary($organizationId, $period);
        $trend = $this->contentMetricRepository->getDailyTrend($organizationId, $period);
        $topContents = $this->contentMetricRepository->findTopByEngagement($organizationId, $period, 5);

        $topContentsData = array_map(fn ($metric) => [
            'content_id' => (string) $metric->contentId,
            'social_account_id' => (string) $metric->socialAccountId,
            'provider' => $metric->provider->value,
            'impressions' => $metric->impressions,
            'reach' => $metric->reach,
            'likes' => $metric->likes,
            'comments' => $metric->comments,
            'shares' => $metric->shares,
            'saves' => $metric->saves,
            'engagement_rate' => $metric->engagementRate,
        ], $topContents);

        return new GetOverviewOutput(
            period: $input->period,
            summary: $summary,
            comparison: $this->calculateComparison($summary, $comparison),
            byNetwork: $byNetwork,
            trend: $trend,
            topContents: $topContentsData,
        );
    }

    private function resolvePeriod(GetOverviewInput $input): MetricPeriod
    {
        if ($input->period === 'custom' && $input->from !== null && $input->to !== null) {
            return MetricPeriod::custom(
                new DateTimeImmutable($input->from),
                new DateTimeImmutable($input->to),
            );
        }

        return MetricPeriod::fromPreset($input->period);
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function calculateComparison(array $current, array $previous): array
    {
        $metrics = ['impressions', 'reach', 'likes', 'comments', 'shares', 'saves', 'clicks', 'engagement_rate'];
        $result = [];

        foreach ($metrics as $metric) {
            $currentVal = (float) ($current[$metric] ?? 0);
            $previousVal = (float) ($previous[$metric] ?? 0);

            $result[$metric] = [
                'current' => $currentVal,
                'previous' => $previousVal,
                'change' => $previousVal > 0
                    ? round(($currentVal - $previousVal) / $previousVal * 100, 2)
                    : 0.0,
            ];
        }

        return $result;
    }
}
