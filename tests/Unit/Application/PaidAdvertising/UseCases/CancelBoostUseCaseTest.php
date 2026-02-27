<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\CancelBoostInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Application\PaidAdvertising\UseCases\CancelBoostUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Exceptions\BoostNotAllowedException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);
    $this->adAccountRepository = Mockery::mock(AdAccountRepositoryInterface::class);
    $this->platformFactory = Mockery::mock(AdPlatformFactoryInterface::class);
    $this->tokenEncryptor = Mockery::mock(AdTokenEncryptorInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CancelBoostUseCase(
        $this->adBoostRepository,
        $this->adAccountRepository,
        $this->platformFactory,
        $this->tokenEncryptor,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->boostId = (string) Uuid::generate();
});

function createBoostForCancelTest(
    string $boostId,
    string $orgId,
    string $userId,
    AdStatus $status = AdStatus::Draft,
    ?array $externalIds = null,
): AdBoost {
    return AdBoost::reconstitute(
        id: Uuid::fromString($boostId),
        organizationId: Uuid::fromString($orgId),
        scheduledPostId: Uuid::generate(),
        adAccountId: Uuid::generate(),
        audienceId: Uuid::generate(),
        budget: AdBudget::create(5000, 'BRL', BudgetType::Daily),
        durationDays: 7,
        objective: AdObjective::Reach,
        status: $status,
        externalIds: $externalIds,
        rejectionReason: null,
        startedAt: $status === AdStatus::Active ? new DateTimeImmutable : null,
        completedAt: null,
        createdBy: Uuid::fromString($userId),
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('cancels a boost successfully', function () {
    $boost = createBoostForCancelTest($this->boostId, $this->orgId, $this->userId);

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);
    $this->adBoostRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new CancelBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        boostId: $this->boostId,
    ));

    expect($output->status)->toBe('cancelled');
});

it('throws BoostNotFoundException when boost does not exist', function () {
    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new CancelBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        boostId: $this->boostId,
    ));
})->throws(BoostNotFoundException::class);

it('throws AdAccountAuthorizationException when boost belongs to different org', function () {
    $otherOrgId = (string) Uuid::generate();
    $boost = createBoostForCancelTest($this->boostId, $otherOrgId, $this->userId);

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);

    $this->useCase->execute(new CancelBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        boostId: $this->boostId,
    ));
})->throws(AdAccountAuthorizationException::class);

it('throws BoostNotAllowedException when boost is in terminal status', function () {
    $boost = createBoostForCancelTest(
        $this->boostId,
        $this->orgId,
        $this->userId,
        AdStatus::Completed,
    );

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);

    $this->useCase->execute(new CancelBoostInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        boostId: $this->boostId,
    ));
})->throws(BoostNotAllowedException::class);
