<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\GetBoostMetricsInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Application\PaidAdvertising\UseCases\GetBoostMetricsUseCase;
use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\Entities\AdMetricSnapshot;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\PaidAdvertising\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);
    $this->metricsRepository = Mockery::mock(AdMetricSnapshotRepositoryInterface::class);

    $this->useCase = new GetBoostMetricsUseCase(
        $this->adBoostRepository,
        $this->metricsRepository,
    );

    $this->orgId = (string) Uuid::generate();
    $this->boostId = (string) Uuid::generate();
});

function createBoostForMetricsTest(string $boostId, string $orgId): AdBoost
{
    return AdBoost::reconstitute(
        id: Uuid::fromString($boostId),
        organizationId: Uuid::fromString($orgId),
        scheduledPostId: Uuid::generate(),
        adAccountId: Uuid::generate(),
        audienceId: Uuid::generate(),
        budget: AdBudget::create(5000, 'BRL', BudgetType::Daily),
        durationDays: 7,
        objective: AdObjective::Reach,
        status: AdStatus::Active,
        externalIds: ['campaign_id' => 'camp_1', 'adset_id' => 'adset_1', 'ad_id' => 'ad_1'],
        rejectionReason: null,
        startedAt: new DateTimeImmutable('-2 days'),
        completedAt: null,
        createdBy: Uuid::generate(),
        createdAt: new DateTimeImmutable('-3 days'),
        updatedAt: new DateTimeImmutable,
    );
}

function createSnapshotForMetricsTest(string $boostId): AdMetricSnapshot
{
    return AdMetricSnapshot::reconstitute(
        id: Uuid::generate(),
        boostId: Uuid::fromString($boostId),
        period: MetricPeriod::Daily,
        impressions: 10000,
        reach: 8000,
        clicks: 250,
        spendCents: 1500,
        spendCurrency: 'BRL',
        conversions: 10,
        ctr: 2.5,
        cpc: 0.06,
        cpm: 0.15,
        costPerConversion: 1.5,
        capturedAt: new DateTimeImmutable,
    );
}

it('returns snapshots and aggregated summary', function () {
    $boost = createBoostForMetricsTest($this->boostId, $this->orgId);
    $snapshot = createSnapshotForMetricsTest($this->boostId);

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);
    $this->metricsRepository->shouldReceive('findByBoostId')->once()->andReturn([$snapshot]);

    $output = $this->useCase->execute(new GetBoostMetricsInput(
        organizationId: $this->orgId,
        boostId: $this->boostId,
    ));

    expect($output->boostId)->toBe($this->boostId)
        ->and($output->snapshots)->toHaveCount(1)
        ->and($output->summary['impressions'])->toBe(10000)
        ->and($output->summary['clicks'])->toBe(250)
        ->and($output->summary['spend_cents'])->toBe(1500);
});

it('throws BoostNotFoundException when boost does not exist', function () {
    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new GetBoostMetricsInput(
        organizationId: $this->orgId,
        boostId: $this->boostId,
    ));
})->throws(BoostNotFoundException::class);

it('throws AdAccountAuthorizationException when boost belongs to different org', function () {
    $otherOrgId = (string) Uuid::generate();
    $boost = createBoostForMetricsTest($this->boostId, $otherOrgId);

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);

    $this->useCase->execute(new GetBoostMetricsInput(
        organizationId: $this->orgId,
        boostId: $this->boostId,
    ));
})->throws(AdAccountAuthorizationException::class);
