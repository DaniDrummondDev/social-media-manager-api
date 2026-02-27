<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\CreateBoostInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\AdAccountNotOperationalException;
use App\Application\PaidAdvertising\UseCases\CreateBoostUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\PaidAdvertising\ValueObjects\DemographicFilter;
use App\Domain\PaidAdvertising\ValueObjects\InterestFilter;
use App\Domain\PaidAdvertising\ValueObjects\LocationFilter;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);
    $this->adAccountRepository = Mockery::mock(AdAccountRepositoryInterface::class);
    $this->audienceRepository = Mockery::mock(AudienceRepositoryInterface::class);
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CreateBoostUseCase(
        $this->adBoostRepository,
        $this->adAccountRepository,
        $this->audienceRepository,
        $this->scheduledPostRepository,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->adAccountId = (string) Uuid::generate();
    $this->audienceId = (string) Uuid::generate();
    $this->scheduledPostId = (string) Uuid::generate();
});

function createAccountForBoostTest(string $orgId): AdAccount
{
    return AdAccount::reconstitute(
        id: Uuid::generate(),
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

function createAudienceForBoostTest(string $orgId): Audience
{
    return Audience::reconstitute(
        id: Uuid::generate(),
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

function createScheduledPostForBoostTest(string $orgId): ScheduledPost
{
    return ScheduledPost::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::fromDateTimeImmutable(new DateTimeImmutable('+1 day')),
        status: PublishingStatus::Pending,
        publishedAt: null,
        externalPostId: null,
        externalPostUrl: null,
        attempts: 0,
        maxAttempts: 3,
        lastAttemptedAt: null,
        lastError: null,
        nextRetryAt: null,
        dispatchedAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('creates a boost in draft status', function () {
    $adAccount = createAccountForBoostTest($this->orgId);
    $audience = createAudienceForBoostTest($this->orgId);
    $scheduledPost = createScheduledPostForBoostTest($this->orgId);

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($adAccount);
    $this->audienceRepository->shouldReceive('findById')->once()->andReturn($audience);
    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($scheduledPost);
    $this->adBoostRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new CreateBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->scheduledPostId,
        adAccountId: $this->adAccountId,
        audienceId: $this->audienceId,
        budgetAmountCents: 5000,
        budgetCurrency: 'BRL',
        budgetType: 'daily',
        durationDays: 7,
        objective: 'reach',
    ));

    expect($output->status)->toBe('draft')
        ->and($output->organizationId)->toBe($this->orgId)
        ->and($output->budgetAmountCents)->toBe(5000)
        ->and($output->budgetCurrency)->toBe('BRL')
        ->and($output->objective)->toBe('reach')
        ->and($output->durationDays)->toBe(7);
});

it('throws AdAccountNotFoundException when ad account does not exist', function () {
    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new CreateBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->scheduledPostId,
        adAccountId: $this->adAccountId,
        audienceId: $this->audienceId,
        budgetAmountCents: 5000,
        budgetCurrency: 'BRL',
        budgetType: 'daily',
        durationDays: 7,
        objective: 'reach',
    ));
})->throws(AdAccountNotFoundException::class);

it('throws AdAccountAuthorizationException when ad account belongs to different org', function () {
    $otherOrgId = (string) Uuid::generate();
    $adAccount = createAccountForBoostTest($otherOrgId);

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($adAccount);

    $this->useCase->execute(new CreateBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->scheduledPostId,
        adAccountId: $this->adAccountId,
        audienceId: $this->audienceId,
        budgetAmountCents: 5000,
        budgetCurrency: 'BRL',
        budgetType: 'daily',
        durationDays: 7,
        objective: 'reach',
    ));
})->throws(AdAccountAuthorizationException::class);

it('throws AudienceNotFoundException when audience does not exist', function () {
    $adAccount = createAccountForBoostTest($this->orgId);

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($adAccount);
    $this->audienceRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new CreateBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->scheduledPostId,
        adAccountId: $this->adAccountId,
        audienceId: $this->audienceId,
        budgetAmountCents: 5000,
        budgetCurrency: 'BRL',
        budgetType: 'daily',
        durationDays: 7,
        objective: 'reach',
    ));
})->throws(AudienceNotFoundException::class);

it('throws AdAccountAuthorizationException when scheduled post not found', function () {
    $adAccount = createAccountForBoostTest($this->orgId);
    $audience = createAudienceForBoostTest($this->orgId);

    $this->adAccountRepository->shouldReceive('findById')->once()->andReturn($adAccount);
    $this->audienceRepository->shouldReceive('findById')->once()->andReturn($audience);
    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new CreateBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->scheduledPostId,
        adAccountId: $this->adAccountId,
        audienceId: $this->audienceId,
        budgetAmountCents: 5000,
        budgetCurrency: 'BRL',
        budgetType: 'daily',
        durationDays: 7,
        objective: 'reach',
    ));
})->throws(AdAccountAuthorizationException::class);
