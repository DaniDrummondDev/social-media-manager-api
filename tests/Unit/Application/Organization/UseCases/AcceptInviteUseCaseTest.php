<?php

declare(strict_types=1);

use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\Exceptions\InvalidTokenException;
use App\Application\Organization\DTOs\AcceptInviteInput;
use App\Application\Organization\DTOs\OrganizationMemberOutput;
use App\Application\Organization\UseCases\AcceptInviteUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Organization\Entities\OrganizationInvite;
use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Exceptions\MemberAlreadyExistsException;
use App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->inviteRepository = Mockery::mock(OrganizationInviteRepositoryInterface::class);
    $this->memberRepository = Mockery::mock(OrganizationMemberRepositoryInterface::class);
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new AcceptInviteUseCase(
        $this->inviteRepository,
        $this->memberRepository,
        $this->userRepository,
        $this->eventDispatcher,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
    $this->orgId = '660e8400-e29b-41d4-a716-446655440000';
    $this->inviterId = '770e8400-e29b-41d4-a716-446655440000';

    $this->invite = OrganizationInvite::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        email: Email::fromString('john@example.com'),
        token: 'invite-token-123',
        role: OrganizationRole::Member,
        invitedBy: Uuid::fromString($this->inviterId),
        acceptedAt: null,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
    );

    $this->user = User::reconstitute(
        id: Uuid::fromString($this->userId),
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
        phone: null,
        timezone: 'America/Sao_Paulo',
        emailVerifiedAt: new DateTimeImmutable,
        twoFactorEnabled: false,
        twoFactorSecret: null,
        recoveryCodes: null,
        status: UserStatus::Active,
        lastLoginAt: null,
        lastLoginIp: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
});

it('accepts invite and creates member', function () {
    $this->inviteRepository->shouldReceive('findByToken')->once()->andReturn($this->invite);
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn(null);
    $this->inviteRepository->shouldReceive('create')->once();
    $this->memberRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new AcceptInviteInput('invite-token-123', $this->userId));

    expect($output)->toBeInstanceOf(OrganizationMemberOutput::class)
        ->and($output->organizationId)->toBe($this->orgId)
        ->and($output->userId)->toBe($this->userId)
        ->and($output->role)->toBe('member');
});

it('throws when invite token not found', function () {
    $this->inviteRepository->shouldReceive('findByToken')->once()->andReturn(null);

    $this->useCase->execute(new AcceptInviteInput('invalid-token', $this->userId));
})->throws(InvalidTokenException::class);

it('throws when user not found', function () {
    $this->inviteRepository->shouldReceive('findByToken')->once()->andReturn($this->invite);
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new AcceptInviteInput('invite-token-123', $this->userId));
})->throws(AuthenticationException::class);

it('throws when already a member', function () {
    $existingMember = OrganizationMember::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        role: OrganizationRole::Member,
        invitedBy: null,
        joinedAt: new DateTimeImmutable,
    );

    $this->inviteRepository->shouldReceive('findByToken')->once()->andReturn($this->invite);
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($existingMember);

    $this->useCase->execute(new AcceptInviteInput('invite-token-123', $this->userId));
})->throws(MemberAlreadyExistsException::class);
