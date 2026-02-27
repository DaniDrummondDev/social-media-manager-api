<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\ListBoostsInput;
use App\Application\PaidAdvertising\UseCases\ListBoostsUseCase;
use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->adBoostRepository = Mockery::mock(AdBoostRepositoryInterface::class);

    $this->useCase = new ListBoostsUseCase(
        $this->adBoostRepository,
    );

    $this->orgId = (string) Uuid::generate();
});

function createBoostForListTest(string $orgId): AdBoost
{
    return AdBoost::reconstitute(
        id: Uuid::generate(),
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

it('lists boosts with cursor pagination', function () {
    $boost = createBoostForListTest($this->orgId);

    $this->adBoostRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [$boost],
            'next_cursor' => 'cursor_abc',
        ]);

    $result = $this->useCase->execute(new ListBoostsInput(
        organizationId: $this->orgId,
        cursor: null,
        limit: 20,
    ));

    expect($result['items'])->toHaveCount(1)
        ->and($result['next_cursor'])->toBe('cursor_abc')
        ->and($result['items'][0]->status)->toBe('draft');
});

it('returns empty array when no boosts exist', function () {
    $this->adBoostRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [],
            'next_cursor' => null,
        ]);

    $result = $this->useCase->execute(new ListBoostsInput(
        organizationId: $this->orgId,
    ));

    expect($result['items'])->toHaveCount(0)
        ->and($result['next_cursor'])->toBeNull();
});
