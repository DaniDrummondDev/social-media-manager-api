<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\BoostMetricsOutput;
use App\Application\PaidAdvertising\DTOs\GetBoostMetricsInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Domain\PaidAdvertising\Entities\AdMetricSnapshot;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetBoostMetricsUseCase
{
    public function __construct(
        private readonly AdBoostRepositoryInterface $adBoostRepository,
        private readonly AdMetricSnapshotRepositoryInterface $metricsRepository,
    ) {}

    public function execute(GetBoostMetricsInput $input): BoostMetricsOutput
    {
        $boost = $this->adBoostRepository->findById(Uuid::fromString($input->boostId));

        if ($boost === null) {
            throw new BoostNotFoundException($input->boostId);
        }

        if ((string) $boost->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        $boostId = Uuid::fromString($input->boostId);

        if ($input->period !== null) {
            $period = MetricPeriod::from($input->period);
            $snapshots = $this->metricsRepository->findByBoostIdAndPeriod($boostId, $period);
        } else {
            $snapshots = $this->metricsRepository->findByBoostId($boostId);
        }

        $snapshotData = array_map(fn (AdMetricSnapshot $s) => [
            'period' => $s->period->value,
            'impressions' => $s->impressions,
            'reach' => $s->reach,
            'clicks' => $s->clicks,
            'spend_cents' => $s->spendCents,
            'spend_currency' => $s->spendCurrency,
            'conversions' => $s->conversions,
            'ctr' => $s->ctr,
            'cpc' => $s->cpc,
            'cpm' => $s->cpm,
            'cost_per_conversion' => $s->costPerConversion,
            'captured_at' => $s->capturedAt->format('c'),
        ], $snapshots);

        $summary = $this->aggregateSummary($snapshots);

        return new BoostMetricsOutput(
            boostId: $input->boostId,
            snapshots: $snapshotData,
            summary: $summary,
        );
    }

    /**
     * @param  array<AdMetricSnapshot>  $snapshots
     * @return array{impressions: int, reach: int, clicks: int, spend_cents: int, conversions: int, ctr: float, cpc: ?float, cpm: ?float}
     */
    private function aggregateSummary(array $snapshots): array
    {
        $totalImpressions = 0;
        $totalReach = 0;
        $totalClicks = 0;
        $totalSpendCents = 0;
        $totalConversions = 0;

        foreach ($snapshots as $snapshot) {
            $totalImpressions += $snapshot->impressions;
            $totalReach += $snapshot->reach;
            $totalClicks += $snapshot->clicks;
            $totalSpendCents += $snapshot->spendCents;
            $totalConversions += $snapshot->conversions;
        }

        $ctr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0.0;
        $cpc = $totalClicks > 0 ? $totalSpendCents / (100 * $totalClicks) : null;
        $cpm = $totalImpressions > 0 ? ($totalSpendCents / (100 * $totalImpressions)) * 1000 : null;

        return [
            'impressions' => $totalImpressions,
            'reach' => $totalReach,
            'clicks' => $totalClicks,
            'spend_cents' => $totalSpendCents,
            'conversions' => $totalConversions,
            'ctr' => $ctr,
            'cpc' => $cpc,
            'cpm' => $cpm,
        ];
    }
}
