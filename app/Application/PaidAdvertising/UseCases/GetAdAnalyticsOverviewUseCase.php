<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\AdAnalyticsOverviewOutput;
use App\Application\PaidAdvertising\DTOs\GetAdAnalyticsOverviewInput;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GetAdAnalyticsOverviewUseCase
{
    public function __construct(
        private readonly AdBoostRepositoryInterface $adBoostRepository,
        private readonly AdMetricSnapshotRepositoryInterface $metricsRepository,
    ) {}

    public function execute(GetAdAnalyticsOverviewInput $input): AdAnalyticsOverviewOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $from = $input->from !== null
            ? new DateTimeImmutable($input->from)
            : new DateTimeImmutable('-30 days');

        $to = $input->to !== null
            ? new DateTimeImmutable($input->to)
            : new DateTimeImmutable;

        $spendData = $this->metricsRepository->getTotalSpend($organizationId, $from, $to);

        $history = $this->metricsRepository->getSpendingHistory($organizationId, $from, $to);

        $totalImpressions = 0;
        $totalClicks = 0;
        $totalConversions = 0;

        foreach ($history as $entry) {
            $totalImpressions += $entry['impressions'];
            $totalClicks += $entry['clicks'];
            $totalConversions += $entry['conversions'];
        }

        $avgCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0.0;
        $avgCpc = $totalClicks > 0 ? $spendData['total_spend_cents'] / (100 * $totalClicks) : null;

        $activeBoosts = count($this->adBoostRepository->findByStatus(AdStatus::Active));
        $completedBoosts = count($this->adBoostRepository->findByStatus(AdStatus::Completed));

        return new AdAnalyticsOverviewOutput(
            totalSpendCents: $spendData['total_spend_cents'],
            currency: $spendData['currency'],
            totalImpressions: $totalImpressions,
            totalClicks: $totalClicks,
            totalConversions: $totalConversions,
            avgCtr: $avgCtr,
            avgCpc: $avgCpc,
            activeBoosts: $activeBoosts,
            completedBoosts: $completedBoosts,
        );
    }
}
