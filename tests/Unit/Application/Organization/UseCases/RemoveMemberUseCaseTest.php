<?php

declare(strict_types=1);

use App\Application\Organization\DTOs\RemoveMemberInput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Organization\UseCases\RemoveMemberUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Exceptions\CannotRemoveLastOwnerException;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->memberRepository = Mockery::mock(OrganizationMemberRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new RemoveMemberUseCase(
        $this->memberRepository,
        $this->eventDispatcher,
    );

    $this->orgId = '660e8400-e29b-41d4-a716-446655440000';
    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
    $this->targetUserId = '880e8400-e29b-41d4-a716-446655440000';
});

function createMember(string $orgId, string $userId, OrganizationRole $role): OrganizationMember
{
    return OrganizationMember::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        userId: Uuid::fromString($userId),
        role: $role,
        invitedBy: null,
        joinedAt: new DateTimeImmutable,
    );
}

it('removes member successfully', function () {
    $admin = createMember($this->orgId, $this->userId, OrganizationRole::Admin);
    $target = createMember($this->orgId, $this->targetUserId, OrganizationRole::Member);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->userId))
        ->andReturn($admin);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->targetUserId))
        ->andReturn($target);

    $this->memberRepository->shouldReceive('delete')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new RemoveMemberInput($this->orgId, $this->userId, $this->targetUserId);
    $this->useCase->execute($input);
});

it('throws when user is not admin', function () {
    $regularMember = createMember($this->orgId, $this->userId, OrganizationRole::Member);

    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($regularMember);

    $input = new RemoveMemberInput($this->orgId, $this->userId, $this->targetUserId);
    $this->useCase->execute($input);
})->throws(AuthorizationException::class);

it('throws when target member not found', function () {
    $admin = createMember($this->orgId, $this->userId, OrganizationRole::Admin);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->userId))
        ->andReturn($admin);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->targetUserId))
        ->andReturn(null);

    $input = new RemoveMemberInput($this->orgId, $this->userId, $this->targetUserId);
    $this->useCase->execute($input);
})->throws(DomainException::class, 'Member not found');

it('throws when removing last owner', function () {
    $admin = createMember($this->orgId, $this->userId, OrganizationRole::Admin);
    $owner = createMember($this->orgId, $this->targetUserId, OrganizationRole::Owner);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->userId))
        ->andReturn($admin);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->targetUserId))
        ->andReturn($owner);

    $this->memberRepository->shouldReceive('listByOrganization')->once()->andReturn([$owner, $admin]);

    $input = new RemoveMemberInput($this->orgId, $this->userId, $this->targetUserId);
    $this->useCase->execute($input);
})->throws(CannotRemoveLastOwnerException::class);
