<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\DTOs\GetAudienceInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\UseCases\GetAudienceUseCase;
use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Exceptions\AudienceNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;

it('returns audience for valid id and organization', function () {
    $orgId = (string) Uuid::generate();
    $audienceId = Uuid::generate();

    $audience = Audience::reconstitute(
        id: $audienceId,
        organizationId: Uuid::fromString($orgId),
        name: 'Test Audience',
        targetingSpec: TargetingSpec::fromArray([
            'demographics' => ['age_min' => 18, 'age_max' => 45],
            'locations' => ['countries' => ['BR']],
            'interests' => [],
        ]),
        providerAudienceIds: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($audience);

    $useCase = new GetAudienceUseCase($repo);
    $result = $useCase->execute(new GetAudienceInput(
        organizationId: $orgId,
        audienceId: (string) $audienceId,
    ));

    expect($result->id)->toBe((string) $audienceId)
        ->and($result->name)->toBe('Test Audience')
        ->and($result->organizationId)->toBe($orgId);
});

it('throws when audience not found', function () {
    $repo = mock(AudienceRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $useCase = new GetAudienceUseCase($repo);
    $useCase->execute(new GetAudienceInput(
        organizationId: (string) Uuid::generate(),
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
        name: 'Test Audience',
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

    $useCase = new GetAudienceUseCase($repo);
    $useCase->execute(new GetAudienceInput(
        organizationId: $differentOrgId,
        audienceId: (string) $audienceId,
    ));
})->throws(AdAccountAuthorizationException::class);
