<?php

declare(strict_types=1);

use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Organization\DTOs\InviteMemberInput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Organization\UseCases\InviteMemberUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Organization\Entities\OrganizationInvite;
use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Exceptions\MemberAlreadyExistsException;
use App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->memberRepository = Mockery::mock(OrganizationMemberRepositoryInterface::class);
    $this->inviteRepository = Mockery::mock(OrganizationInviteRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new InviteMemberUseCase(
        $this->memberRepository,
        $this->inviteRepository,
        $this->eventDispatcher,
    );

    $this->orgId = '660e8400-e29b-41d4-a716-446655440000';
    $this->userId = '550e8400-e29b-41d4-a716-446655440000';

    $this->adminMember = OrganizationMember::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        role: OrganizationRole::Admin,
        invitedBy: null,
        joinedAt: new DateTimeImmutable,
    );
});

it('sends invitation successfully', function () {
    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($this->adminMember);
    $this->inviteRepository->shouldReceive('findPendingByOrgAndEmail')->once()->andReturn(null);
    $this->inviteRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new InviteMemberInput($this->orgId, $this->userId, 'invite@example.com', 'member');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(MessageOutput::class)
        ->and($output->message)->toContain('invite@example.com');
});

it('throws when pending invite already exists', function () {
    $existingInvite = OrganizationInvite::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        email: Email::fromString('invite@example.com'),
        token: 'existing-token',
        role: OrganizationRole::Member,
        invitedBy: Uuid::fromString($this->userId),
        acceptedAt: null,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
    );

    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($this->adminMember);
    $this->inviteRepository->shouldReceive('findPendingByOrgAndEmail')->once()->andReturn($existingInvite);

    $input = new InviteMemberInput($this->orgId, $this->userId, 'invite@example.com', 'member');
    $this->useCase->execute($input);
})->throws(MemberAlreadyExistsException::class);

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

    $input = new InviteMemberInput($this->orgId, $this->userId, 'invite@example.com', 'member');
    $this->useCase->execute($input);
})->throws(AuthorizationException::class);

it('throws when user is not a member', function () {
    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn(null);

    $input = new InviteMemberInput($this->orgId, $this->userId, 'invite@example.com', 'member');
    $this->useCase->execute($input);
})->throws(AuthorizationException::class);
