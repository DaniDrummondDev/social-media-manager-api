<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\GetSpendingHistoryInput;
use App\Application\PaidAdvertising\UseCases\GetSpendingHistoryUseCase;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->metricsRepository = Mockery::mock(AdMetricSnapshotRepositoryInterface::class);

    $this->useCase = new GetSpendingHistoryUseCase(
        $this->metricsRepository,
    );

    $this->orgId = (string) Uuid::generate();
});

it('returns daily spending history', function () {
    $historyData = [
        ['date' => '2026-02-25', 'spend_cents' => 2500, 'impressions' => 5000, 'clicks' => 100, 'conversions' => 5],
        ['date' => '2026-02-26', 'spend_cents' => 3000, 'impressions' => 6000, 'clicks' => 120, 'conversions' => 8],
        ['date' => '2026-02-27', 'spend_cents' => 2800, 'impressions' => 5500, 'clicks' => 110, 'conversions' => 6],
    ];

    $this->metricsRepository->shouldReceive('getSpendingHistory')
        ->once()
        ->andReturn($historyData);

    $output = $this->useCase->execute(new GetSpendingHistoryInput(
        organizationId: $this->orgId,
        from: '2026-02-25',
        to: '2026-02-27',
    ));

    expect($output->history)->toHaveCount(3)
        ->and($output->history[0]['date'])->toBe('2026-02-25')
        ->and($output->history[0]['spend_cents'])->toBe(2500)
        ->and($output->history[2]['conversions'])->toBe(6);
});

it('returns empty history when no data exists', function () {
    $this->metricsRepository->shouldReceive('getSpendingHistory')
        ->once()
        ->andReturn([]);

    $output = $this->useCase->execute(new GetSpendingHistoryInput(
        organizationId: $this->orgId,
        from: '2026-02-01',
        to: '2026-02-28',
    ));

    expect($output->history)->toHaveCount(0);
});
