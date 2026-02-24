<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\AuthTokensOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Organization\DTOs\SwitchOrganizationInput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Organization\UseCases\SwitchOrganizationUseCase;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Organization\ValueObjects\OrganizationStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->memberRepository = Mockery::mock(OrganizationMemberRepositoryInterface::class);
    $this->organizationRepository = Mockery::mock(OrganizationRepositoryInterface::class);
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->authTokenService = Mockery::mock(AuthTokenServiceInterface::class);
    $this->refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
    $this->hashService = Mockery::mock(HashServiceInterface::class);

    $this->useCase = new SwitchOrganizationUseCase(
        $this->memberRepository,
        $this->organizationRepository,
        $this->userRepository,
        $this->authTokenService,
        $this->refreshTokenRepository,
        $this->hashService,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
    $this->orgId = '660e8400-e29b-41d4-a716-446655440000';

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

    $this->organization = Organization::reconstitute(
        id: Uuid::fromString($this->orgId),
        name: 'My Company',
        slug: OrganizationSlug::fromString('my-company'),
        timezone: 'America/Sao_Paulo',
        status: OrganizationStatus::Active,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->member = OrganizationMember::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        role: OrganizationRole::Admin,
        invitedBy: null,
        joinedAt: new DateTimeImmutable,
    );
});

it('switches organization and returns new tokens', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->organizationRepository->shouldReceive('findById')->once()->andReturn($this->organization);
    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn($this->member);
    $this->authTokenService->shouldReceive('generateAccessToken')->once()->andReturn([
        'token' => 'new-access-token',
        'jti' => 'jti-789',
        'expires_in' => 900,
    ]);
    $this->authTokenService->shouldReceive('generateRefreshToken')->once()->andReturn('new-refresh-token');
    $this->hashService->shouldReceive('hash')->once()->andReturn('hashed-refresh');
    $this->refreshTokenRepository->shouldReceive('store')->once();

    $output = $this->useCase->execute(new SwitchOrganizationInput($this->userId, $this->orgId));

    expect($output)->toBeInstanceOf(AuthTokensOutput::class)
        ->and($output->accessToken)->toBe('new-access-token')
        ->and($output->refreshToken)->toBe('new-refresh-token')
        ->and($output->tokenType)->toBe('Bearer');
});

it('throws when user not found', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new SwitchOrganizationInput($this->userId, $this->orgId));
})->throws(AuthenticationException::class);

it('throws when organization not found', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->organizationRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new SwitchOrganizationInput($this->userId, $this->orgId));
})->throws(AuthorizationException::class);

it('throws when organization is inactive', function () {
    $suspendedOrg = Organization::reconstitute(
        id: Uuid::fromString($this->orgId),
        name: 'Suspended Org',
        slug: OrganizationSlug::fromString('suspended-org'),
        timezone: 'America/Sao_Paulo',
        status: OrganizationStatus::Suspended,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->organizationRepository->shouldReceive('findById')->once()->andReturn($suspendedOrg);

    $this->useCase->execute(new SwitchOrganizationInput($this->userId, $this->orgId));
})->throws(AuthorizationException::class);

it('throws when not a member', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->organizationRepository->shouldReceive('findById')->once()->andReturn($this->organization);
    $this->memberRepository->shouldReceive('findByOrgAndUser')->once()->andReturn(null);

    $this->useCase->execute(new SwitchOrganizationInput($this->userId, $this->orgId));
})->throws(AuthorizationException::class);
