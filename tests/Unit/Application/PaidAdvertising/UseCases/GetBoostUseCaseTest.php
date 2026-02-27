<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\GetBoostInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\BoostNotFoundException;
use App\Application\PaidAdvertising\UseCases\GetBoostUseCase;
use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);

    $this->useCase = new GetBoostUseCase(
        $this->adBoostRepository,
    );

    $this->orgId = (string) Uuid::generate();
    $this->boostId = (string) Uuid::generate();
});

function createBoostForGetTest(string $boostId, string $orgId): AdBoost
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
        status: AdStatus::Draft,
        externalIds: null,
        rejectionReason: null,
        startedAt: null,
        completedAt: null,
        createdBy: Uuid::generate(),
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('returns boost output for valid request', function () {
    $boost = createBoostForGetTest($this->boostId, $this->orgId);

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);

    $output = $this->useCase->execute(new GetBoostInput(
        organizationId: $this->orgId,
        boostId: $this->boostId,
    ));

    expect($output->id)->toBe($this->boostId)
        ->and($output->organizationId)->toBe($this->orgId)
        ->and($output->status)->toBe('draft');
});

it('throws BoostNotFoundException when boost does not exist', function () {
    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new GetBoostInput(
        organizationId: $this->orgId,
        boostId: $this->boostId,
    ));
})->throws(BoostNotFoundException::class);

it('throws AdAccountAuthorizationException when boost belongs to different org', function () {
    $otherOrgId = (string) Uuid::generate();
    $boost = createBoostForGetTest($this->boostId, $otherOrgId);

    $this->adBoostRepository->shouldReceive('findById')->once()->andReturn($boost);

    $this->useCase->execute(new GetBoostInput(
        organizationId: $this->orgId,
        boostId: $this->boostId,
    ));
})->throws(AdAccountAuthorizationException::class);
