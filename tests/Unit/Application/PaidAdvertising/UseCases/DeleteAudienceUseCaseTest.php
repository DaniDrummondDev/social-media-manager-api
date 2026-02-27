<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\DeleteAudienceInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\UseCases\DeleteAudienceUseCase;
use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;

it('deletes audience successfully', function () {
    $orgId = (string) Uuid::generate();
    $audienceId = Uuid::generate();

    $audience = Audience::reconstitute(
        id: $audienceId,
        organizationId: Uuid::fromString($orgId),
        name: 'To Delete',
        targetingSpec: TargetingSpec::fromArray([
            'demographics' => [],
            'locations' => [],
            'interests' => [],
        ]),
        providerAudienceIds: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($audience);
    $repo->shouldReceive('delete')->once();

    $useCase = new DeleteAudienceUseCase($repo);
    $useCase->execute(new DeleteAudienceInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        audienceId: (string) $audienceId,
    ));

    expect(true)->toBeTrue();
});

it('throws when audience not found', function () {
    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $useCase = new DeleteAudienceUseCase($repo);
    $useCase->execute(new DeleteAudienceInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        audienceId: (string) Uuid::generate(),
    ));
})->throws(AudienceNotFoundException::class);

it('throws when organization does not own the audience', function () {
    $orgId = (string) Uuid::generate();
    $differentOrgId = (string) Uuid::generate();
    $audienceId = Uuid::generate();

    $audience = Audience::reconstitute(
        id: $audienceId,
        organizationId: Uuid::fromString($orgId),
        name: 'Not Yours',
        targetingSpec: TargetingSpec::fromArray([
            'demographics' => [],
            'locations' => [],
            'interests' => [],
        ]),
        providerAudienceIds: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($audience);
    $repo->shouldNotReceive('delete');

    $useCase = new DeleteAudienceUseCase($repo);
    $useCase->execute(new DeleteAudienceInput(
        organizationId: $differentOrgId,
        userId: (string) Uuid::generate(),
        audienceId: (string) $audienceId,
    ));
})->throws(AdAccountAuthorizationException::class);
