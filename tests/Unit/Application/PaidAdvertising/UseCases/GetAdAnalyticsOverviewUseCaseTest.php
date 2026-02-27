<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\GetAdAnalyticsOverviewInput;
use App\Application\PaidAdvertising\UseCases\GetAdAnalyticsOverviewUseCase;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);
    $this->metricsRepository = Mockery::mock(AdMetricSnapshotRepositoryInterface::class);

    $this->useCase = new GetAdAnalyticsOverviewUseCase(
        $this->adBoostRepository,
        $this->metricsRepository,
    );

    $this->orgId = (string) Uuid::generate();
});

it('returns aggregated overview with total spend, impressions and boost counts', function () {
    $this->metricsRepository->shouldReceive('getTotalSpend')
        ->once()
        ->andReturn(['total_spend_cents' => 50000, 'currency' => 'BRL']);

    $this->metricsRepository->shouldReceive('getSpendingHistory')
        ->once()
        ->andReturn([
            ['date' => '2026-02-25', 'spend_cents' => 25000, 'impressions' => 50000, 'clicks' => 1000, 'conversions' => 50],
            ['date' => '2026-02-26', 'spend_cents' => 25000, 'impressions' => 60000, 'clicks' => 1200, 'conversions' => 40],
        ]);

    $this->adBoostRepository->shouldReceive('findByStatus')
        ->with(AdStatus::Active)
        ->once()
        ->andReturn([new stdClass, new stdClass]);

    $this->adBoostRepository->shouldReceive('findByStatus')
        ->with(AdStatus::Completed)
        ->once()
        ->andReturn([new stdClass]);

    $output = $this->useCase->execute(new GetAdAnalyticsOverviewInput(
        organizationId: $this->orgId,
        from: '2026-02-25',
        to: '2026-02-27',
    ));

    expect($output->totalSpendCents)->toBe(50000)
        ->and($output->currency)->toBe('BRL')
        ->and($output->totalImpressions)->toBe(110000)
        ->and($output->totalClicks)->toBe(2200)
        ->and($output->totalConversions)->toBe(90)
        ->and($output->activeBoosts)->toBe(2)
        ->and($output->completedBoosts)->toBe(1)
        ->and($output->avgCtr)->toBeGreaterThan(0);
});

it('defaults to last 30 days when no dates provided', function () {
    $this->metricsRepository->shouldReceive('getTotalSpend')
        ->once()
        ->andReturn(['total_spend_cents' => 0, 'currency' => 'BRL']);

    $this->metricsRepository->shouldReceive('getSpendingHistory')
        ->once()
        ->andReturn([]);

    $this->adBoostRepository->shouldReceive('findByStatus')
        ->with(AdStatus::Active)
        ->once()
        ->andReturn([]);

    $this->adBoostRepository->shouldReceive('findByStatus')
        ->with(AdStatus::Completed)
        ->once()
        ->andReturn([]);

    $output = $this->useCase->execute(new GetAdAnalyticsOverviewInput(
        organizationId: $this->orgId,
    ));

    expect($output->totalSpendCents)->toBe(0)
        ->and($output->totalImpressions)->toBe(0)
        ->and($output->activeBoosts)->toBe(0)
        ->and($output->completedBoosts)->toBe(0)
        ->and($output->avgCtr)->toBe(0.0);
});
