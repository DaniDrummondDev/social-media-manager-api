<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\SyncAdMetricsInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountNotOperationalException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Application\PaidAdvertising\UseCases\SyncAdMetricsUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);
    $this->adAccountRepository = Mockery::mock(AdAccountRepositoryInterface::class);
    $this->platformFactory = Mockery::mock(AdPlatformFactoryInterface::class);
    $this->tokenEncryptor = Mockery::mock(AdTokenEncryptorInterface::class);
    $this->metricsRepository = Mockery::mock(AdMetricSnapshotRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new SyncAdMetricsUseCase(
        $this->adBoostRepository,
        $this->adAccountRepository,
        $this->platformFactory,
        $this->tokenEncryptor,
        $this->metricsRepository,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->boostId = (string) Uuid::generate();
    $this->adAccountId = Uuid::generate();
});

function createActiveBoostForSyncTest(
    string $boostId,
    string $orgId,
    Uuid $adAccountId,
    string $userId,
): AdBoost {
    return AdBoost::reconstitute(
        id: Uuid::fromString($boostId),
        organizationId: Uuid::fromString($orgId),
        scheduledPostId: Uuid::generate(),
        adAccountId: $adAccountId,
        audienceId: Uuid::generate(),
        budget: AdBudget::create(5000, 'BRL', BudgetType::Daily),
        durationDays: 7,
        objective: AdObjective::Reach,
        status: AdStatus::Active,
        externalIds: [
            'campaign_id' => 'camp_ext_1',
            'adset_id' => 'adset_ext_1',
            'ad_id' => 'ad_ext_1',
        ],
        rejectionReason: null,
        startedAt: new DateTimeImmutable('-2 days'),
        completedAt: null,
        createdBy: Uuid::fromString($userId),
        createdAt: new DateTimeImmutable('-3 days'),
        updatedAt: new DateTimeImmutable,
    );
}

function createActiveAccountForSyncTest(string $orgId, Uuid $accountId): AdAccount
{
    return AdAccount::reconstitute(
        id: $accountId,
        organizationId: Uuid::fromString($orgId),
        connectedBy: Uuid::generate(),
        provider: AdProvider::Meta,
        providerAccountId: 'act_123',
        providerAccountName: 'Test',
        credentials: AdAccountCredentials::create('enc-token', null, new DateTimeImmutable('+2h'), ['ads_read']),
        status: AdAccountStatus::Active,
        metadata: null,
        connectedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('fetches metrics from platform, creates snapshot and dispatches AdMetricsSynced', function () {
    $boost = createActiveBoostForSyncTest($this->boostId, $this->orgId, $this->adAccountId, $this->userId);
    $adAccount = createActiveAccountForSyncTest($this->orgId, $this->adAccountId);
    $platform = Mockery::mock(AdPlatformInterface::class);

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);
    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($adAccount);
    $this->platformFactory->shouldReceive('make')->once()->with(AdProvider::Meta)->andReturn($platform);
    $this->tokenEncryptor->shouldReceive('decrypt')->once()->with('enc-token')->andReturn('plain-token');

    $platform->shouldReceive('getMetrics')->once()->andReturn([
        'impressions' => 10000,
        'reach' => 8000,
        'clicks' => 250,
        'spend_cents' => 1500,
        'conversions' => 10,
    ]);

    $this->metricsRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $this->useCase->execute(new SyncAdMetricsInput(
        boostId: $this->boostId,
    ));
});

it('throws BoostNotFoundException when boost does not exist', function () {
    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new SyncAdMetricsInput(
        boostId: $this->boostId,
    ));
})->throws(BoostNotFoundException::class);

it('throws AdAccountNotOperationalException when account is not operational', function () {
    $boost = createActiveBoostForSyncTest($this->boostId, $this->orgId, $this->adAccountId, $this->userId);

    $suspendedAccount = AdAccount::reconstitute(
        id: $this->adAccountId,
        organizationId: Uuid::fromString($this->orgId),
        connectedBy: Uuid::generate(),
        provider: AdProvider::Meta,
        providerAccountId: 'act_123',
        providerAccountName: 'Test',
        credentials: AdAccountCredentials::create('enc-token', null, new DateTimeImmutable('+2h'), ['ads_read']),
        status: AdAccountStatus::Suspended,
        metadata: null,
        connectedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);
    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($suspendedAccount);

    $this->useCase->execute(new SyncAdMetricsInput(
        boostId: $this->boostId,
    ));
})->throws(AdAccountNotOperationalException::class);
