<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\SubmitBoostToPlatformInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountNotOperationalException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Application\PaidAdvertising\UseCases\SubmitBoostToPlatformUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\PaidAdvertising\ValueObjects\DemographicFilter;
use App\Domain\PaidAdvertising\ValueObjects\InterestFilter;
use App\Domain\PaidAdvertising\ValueObjects\LocationFilter;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);
    $this->adAccountRepository = Mockery::mock(AdAccountRepositoryInterface::class);
    $this->audienceRepository = Mockery::mock(AudienceRepositoryInterface::class);
    $this->platformFactory = Mockery::mock(AdPlatformFactoryInterface::class);
    $this->tokenEncryptor = Mockery::mock(AdTokenEncryptorInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new SubmitBoostToPlatformUseCase(
        $this->adBoostRepository,
        $this->adAccountRepository,
        $this->audienceRepository,
        $this->platformFactory,
        $this->tokenEncryptor,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->boostId = (string) Uuid::generate();
    $this->adAccountId = Uuid::generate();
    $this->audienceId = Uuid::generate();
});

function createDraftBoostForSubmitTest(
    string $boostId,
    string $orgId,
    Uuid $adAccountId,
    Uuid $audienceId,
    string $userId,
): AdBoost {
    return AdBoost::reconstitute(
        id: Uuid::fromString($boostId),
        organizationId: Uuid::fromString($orgId),
        scheduledPostId: Uuid::generate(),
        adAccountId: $adAccountId,
        audienceId: $audienceId,
        budget: AdBudget::create(5000, 'BRL', BudgetType::Daily),
        durationDays: 7,
        objective: AdObjective::Reach,
        status: AdStatus::Draft,
        externalIds: null,
        rejectionReason: null,
        startedAt: null,
        completedAt: null,
        createdBy: Uuid::fromString($userId),
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

function createActiveAccountForSubmitTest(string $orgId, Uuid $accountId): AdAccount
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

function createAudienceForSubmitTest(string $orgId, Uuid $audienceId): Audience
{
    return Audience::reconstitute(
        id: $audienceId,
        organizationId: Uuid::fromString($orgId),
        name: 'Test Audience',
        targetingSpec: TargetingSpec::create(
            DemographicFilter::create(18, 45),
            LocationFilter::create(['BR']),
            InterestFilter::create(),
        ),
        providerAudienceIds: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('submits boost to platform, creates campaign+adset+ad and activates', function () {
    $boost = createDraftBoostForSubmitTest(
        $this->boostId,
        $this->orgId,
        $this->adAccountId,
        $this->audienceId,
        $this->userId,
    );
    $adAccount = createActiveAccountForSubmitTest($this->orgId, $this->adAccountId);
    $audience = createAudienceForSubmitTest($this->orgId, $this->audienceId);

    $platform = Mockery::mock(AdPlatformInterface::class);

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);
    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($adAccount);
    $this->audienceRepository->shouldReceive('findById')->once()->andReturn($audience);
    $this->platformFactory->shouldReceive('make')->once()->with(AdProvider::Meta)->andReturn($platform);
    $this->tokenEncryptor->shouldReceive('decrypt')->once()->with('enc-token')->andReturn('plain-token');

    $platform->shouldReceive('createCampaign')->once()->andReturn(['campaign_id' => 'camp_ext_1']);
    $platform->shouldReceive('createAdSet')->once()->andReturn(['adset_id' => 'adset_ext_1']);
    $platform->shouldReceive('createAd')->once()->andReturn(['ad_id' => 'ad_ext_1']);

    $this->adBoostRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $this->useCase->execute(new SubmitBoostToPlatformInput(
        boostId: $this->boostId,
    ));
});

it('throws BoostNotFoundException when boost does not exist', function () {
    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new SubmitBoostToPlatformInput(
        boostId: $this->boostId,
    ));
})->throws(BoostNotFoundException::class);

it('throws AdAccountNotOperationalException when account is not operational', function () {
    $boost = createDraftBoostForSubmitTest(
        $this->boostId,
        $this->orgId,
        $this->adAccountId,
        $this->audienceId,
        $this->userId,
    );

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

    $this->useCase->execute(new SubmitBoostToPlatformInput(
        boostId: $this->boostId,
    ));
})->throws(AdAccountNotOperationalException::class);
