<?php

declare(strict_types=1);

use App\Application\Organization\DTOs\OrganizationOutput;
use App\Application\Organization\DTOs\UpdateOrganizationInput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Organization\UseCases\UpdateOrganizationUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Organization\ValueObjects\OrganizationStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->organizationRepository = Mockery::mock(OrganizationRepositoryInterface::class);
    $this->memberRepository = Mockery::mock(OrganizationMemberRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new UpdateOrganizationUseCase(
        $this->organizationRepository,
        $this->memberRepository,
        $this->eventDispatcher,
    );

    $this->orgId = '660e8400-e29b-41d4-a716-446655440000';
    $this->userId = '550e8400-e29b-41d4-a716-446655440000';

    $this->organization = Organization::reconstitute(
        id: Uuid::fromString($this->orgId),
        name: 'Old Name',
        slug: OrganizationSlug::fromString('old-name'),
        timezone: 'America/Sao_Paulo',
        status: OrganizationStatus::Active,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->adminMember = OrganizationMember::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        role: OrganizationRole::Admin,
        invitedBy: null,
        joinedAt: new DateTimeImmutable,
    );
});

it('updates organization successfully', function () {
    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($this->adminMember);
    $this->organizationRepository->shouldReceive('findById')->once()->andReturn($this->organization);
    $this->organizationRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new UpdateOrganizationInput($this->orgId, $this->userId, name: 'New Name');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(OrganizationOutput::class)
        ->and($output->name)->toBe('New Name');
});

it('throws when user is not admin', function () {
    $regularMember = OrganizationMember::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        role: OrganizationRole::Member,
        invitedBy: null,
        joinedAt: new DateTimeImmutable,
    );

    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($regularMember);

    $input = new UpdateOrganizationInput($this->orgId, $this->userId, name: 'New Name');
    $this->useCase->execute($input);
})->throws(AuthorizationException::class);

it('throws when user is not a member', function () {
    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn(null);

    $input = new UpdateOrganizationInput($this->orgId, $this->userId, name: 'New Name');
    $this->useCase->execute($input);
})->throws(AuthorizationException::class);
