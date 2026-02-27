<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\ListAudiencesInput;
use App\Application\PaidAdvertising\UseCases\ListAudiencesUseCase;
use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;

it('lists audiences for organization', function () {
    $orgId = (string) Uuid::generate();

    $audience1 = Audience::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        name: 'Audience A',
        targetingSpec: TargetingSpec::fromArray([
            'demographics' => [],
            'locations' => [],
            'interests' => [],
        ]),
        providerAudienceIds: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $audience2 = Audience::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        name: 'Audience B',
        targetingSpec: TargetingSpec::fromArray([
            'demographics' => ['age_min' => 18, 'age_max' => 35],
            'locations' => ['countries' => ['BR']],
            'interests' => [],
        ]),
        providerAudienceIds: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findByOrganizationId')->once()->andReturn([$audience1, $audience2]);

    $useCase = new ListAudiencesUseCase($repo);
    $result = $useCase->execute(new ListAudiencesInput(organizationId: $orgId));

    expect($result)->toHaveCount(2)
        ->and($result[0]->name)->toBe('Audience A')
        ->and($result[1]->name)->toBe('Audience B');
});

it('returns empty array when no audiences exist', function () {
    $orgId = (string) Uuid::generate();

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $useCase = new ListAudiencesUseCase($repo);
    $result = $useCase->execute(new ListAudiencesInput(organizationId: $orgId));

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});
