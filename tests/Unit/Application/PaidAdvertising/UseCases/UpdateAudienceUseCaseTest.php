<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\UpdateAudienceInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\DuplicateAudienceNameException;
use App\Application\PaidAdvertising\UseCases\UpdateAudienceUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestAudienceForUseCase(string $orgId): Audience
{
    return Audience::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        name: 'Original Name',
        targetingSpec: TargetingSpec::fromArray([
            'demographics' => [],
            'locations' => [],
            'interests' => [],
        ]),
        providerAudienceIds: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('updates audience name and targeting spec', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $audience = createTestAudienceForUseCase($orgId);

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($audience);
    $repo->shouldReceive('existsByNameAndOrganization')->once()->andReturn(false);
    $repo->shouldReceive('update')->once();

    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new UpdateAudienceUseCase($repo, $dispatcher);
    $result = $useCase->execute(new UpdateAudienceInput(
        organizationId: $orgId,
        userId: $userId,
        audienceId: (string) $audience->id,
        name: 'Updated Name',
        targetingSpec: [
            'demographics' => ['age_min' => 25, 'age_max' => 55],
            'locations' => ['countries' => ['US']],
            'interests' => [],
        ],
    ));

    expect($result->name)->toBe('Updated Name')
        ->and($result->organizationId)->toBe($orgId);
});

it('throws when audience not found', function () {
    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $dispatcher = mock(EventDispatcherInterface::class);

    $useCase = new UpdateAudienceUseCase($repo, $dispatcher);
    $useCase->execute(new UpdateAudienceInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        audienceId: (string) Uuid::generate(),
        name: 'New Name',
    ));
})->throws(AudienceNotFoundException::class);

it('throws when organization does not own the audience', function () {
    $orgId = (string) Uuid::generate();
    $differentOrgId = (string) Uuid::generate();
    $audience = createTestAudienceForUseCase($orgId);

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($audience);

    $dispatcher = mock(EventDispatcherInterface::class);

    $useCase = new UpdateAudienceUseCase($repo, $dispatcher);
    $useCase->execute(new UpdateAudienceInput(
        organizationId: $differentOrgId,
        userId: (string) Uuid::generate(),
        audienceId: (string) $audience->id,
        name: 'New Name',
    ));
})->throws(AdAccountAuthorizationException::class);

it('throws when new name is duplicate', function () {
    $orgId = (string) Uuid::generate();
    $audience = createTestAudienceForUseCase($orgId);

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($audience);
    $repo->shouldReceive('existsByNameAndOrganization')->once()->andReturn(true);

    $dispatcher = mock(EventDispatcherInterface::class);

    $useCase = new UpdateAudienceUseCase($repo, $dispatcher);
    $useCase->execute(new UpdateAudienceInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        audienceId: (string) $audience->id,
        name: 'Duplicate Name',
    ));
})->throws(DuplicateAudienceNameException::class);
