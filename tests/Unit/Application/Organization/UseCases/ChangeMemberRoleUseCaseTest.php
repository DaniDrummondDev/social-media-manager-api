<?php

declare(strict_types=1);

use App\Application\Organization\DTOs\ChangeMemberRoleInput;
use App\Application\Organization\DTOs\OrganizationMemberOutput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Organization\UseCases\ChangeMemberRoleUseCase;
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

    $this->useCase = new ChangeMemberRoleUseCase(
        $this->memberRepository,
        $this->eventDispatcher,
    );

    $this->orgId = '660e8400-e29b-41d4-a716-446655440000';
    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
    $this->targetUserId = '880e8400-e29b-41d4-a716-446655440000';
});

function createRoleMember(string $orgId, string $userId, OrganizationRole $role): OrganizationMember
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

it('changes member role successfully', function () {
    $admin = createRoleMember($this->orgId, $this->userId, OrganizationRole::Admin);
    $target = createRoleMember($this->orgId, $this->targetUserId, OrganizationRole::Member);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->userId))
        ->andReturn($admin);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->targetUserId))
        ->andReturn($target);

    $this->memberRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new ChangeMemberRoleInput($this->orgId, $this->userId, $this->targetUserId, 'admin');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(OrganizationMemberOutput::class)
        ->and($output->role)->toBe('admin');
});

it('throws when non-owner promotes to owner', function () {
    $admin = createRoleMember($this->orgId, $this->userId, OrganizationRole::Admin);

    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($admin);

    $input = new ChangeMemberRoleInput($this->orgId, $this->userId, $this->targetUserId, 'owner');
    $this->useCase->execute($input);
})->throws(AuthorizationException::class, 'Only owners can promote to owner');

it('throws when demoting last owner', function () {
    $owner = createRoleMember($this->orgId, $this->userId, OrganizationRole::Owner);
    $targetOwner = createRoleMember($this->orgId, $this->targetUserId, OrganizationRole::Owner);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->userId))
        ->andReturn($owner);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->targetUserId))
        ->andReturn($targetOwner);

    $this->memberRepository->shouldReceive('listByOrganization')->once()->andReturn([$targetOwner]);

    $input = new ChangeMemberRoleInput($this->orgId, $this->userId, $this->targetUserId, 'admin');
    $this->useCase->execute($input);
})->throws(CannotRemoveLastOwnerException::class);

it('throws when user is not admin', function () {
    $regularMember = createRoleMember($this->orgId, $this->userId, OrganizationRole::Member);

    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($regularMember);

    $input = new ChangeMemberRoleInput($this->orgId, $this->userId, $this->targetUserId, 'admin');
    $this->useCase->execute($input);
})->throws(AuthorizationException::class);

it('throws when target member not found', function () {
    $admin = createRoleMember($this->orgId, $this->userId, OrganizationRole::Admin);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->userId))
        ->andReturn($admin);

    $this->memberRepository->shouldReceive('findByOrgAndUser')
        ->once()
        ->with(Mockery::on(fn ($id) => (string) $id === $this->orgId), Mockery::on(fn ($id) => (string) $id === $this->targetUserId))
        ->andReturn(null);

    $input = new ChangeMemberRoleInput($this->orgId, $this->userId, $this->targetUserId, 'admin');
    $this->useCase->execute($input);
})->throws(DomainException::class, 'Member not found');
