<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\CreateAudienceInput;
use App\Application\PaidAdvertising\Exceptions\DuplicateAudienceNameException;
use App\Application\PaidAdvertising\UseCases\CreateAudienceUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('creates audience and dispatches events', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('existsByNameAndOrganization')->once()->andReturn(false);
    $repo->shouldReceive('create')->once();

    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new CreateAudienceUseCase($repo, $dispatcher);
    $result = $useCase->execute(new CreateAudienceInput(
        organizationId: $orgId,
        userId: $userId,
        name: 'Young Males BR',
        targetingSpec: [
            'demographics' => ['age_min' => 18, 'age_max' => 45],
            'locations' => ['countries' => ['BR']],
            'interests' => ['interests' => [['id' => '1', 'name' => 'Tech']]],
        ],
    ));

    expect($result->name)->toBe('Young Males BR')
        ->and($result->organizationId)->toBe($orgId);
});

it('throws when audience name is duplicate', function () {
    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('existsByNameAndOrganization')->once()->andReturn(true);
    $repo->shouldNotReceive('create');

    $dispatcher = mock(EventDispatcherInterface::class);

    $useCase = new CreateAudienceUseCase($repo, $dispatcher);
    $useCase->execute(new CreateAudienceInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        name: 'Existing Name',
        targetingSpec: ['demographics' => [], 'locations' => [], 'interests' => []],
    ));
})->throws(DuplicateAudienceNameException::class);
