<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\AuthTokensOutput;
use App\Application\Identity\DTOs\RefreshTokenInput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\UseCases\RefreshTokenUseCase;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
    $this->authTokenService = Mockery::mock(AuthTokenServiceInterface::class);
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->hashService = Mockery::mock(HashServiceInterface::class);

    $this->useCase = new RefreshTokenUseCase(
        $this->refreshTokenRepository,
        $this->authTokenService,
        $this->userRepository,
        $this->hashService,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
});

it('rotates refresh token successfully', function () {
    $record = [
        'id' => 'token-id',
        'user_id' => $this->userId,
        'token_hash' => 'hashed-token',
        'family_id' => 'family-123',
        'expires_at' => (new DateTimeImmutable('+7 days'))->format('c'),
        'revoked_at' => null,
    ];

    $user = User::reconstitute(
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

    $this->hashService->shouldReceive('hash')->once()->with('old-refresh-token')->andReturn('hashed-token');
    $this->refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($record);
    $this->refreshTokenRepository->shouldReceive('revokeById')->once()->with('token-id');
    $this->userRepository->shouldReceive('findById')->once()->andReturn($user);
    $this->authTokenService->shouldReceive('generateAccessToken')->once()->andReturn([
        'token' => 'new-access-token',
        'jti' => 'jti-456',
        'expires_in' => 900,
    ]);
    $this->authTokenService->shouldReceive('generateRefreshToken')->once()->andReturn('new-refresh-token');
    $this->hashService->shouldReceive('hash')->once()->with('new-refresh-token')->andReturn('new-hashed');
    $this->refreshTokenRepository->shouldReceive('store')->once();

    $output = $this->useCase->execute(new RefreshTokenInput('old-refresh-token'));

    expect($output)->toBeInstanceOf(AuthTokensOutput::class)
        ->and($output->accessToken)->toBe('new-access-token')
        ->and($output->refreshToken)->toBe('new-refresh-token');
});

it('detects token reuse and revokes family', function () {
    $record = [
        'id' => 'token-id',
        'user_id' => $this->userId,
        'token_hash' => 'hashed-token',
        'family_id' => 'family-123',
        'expires_at' => (new DateTimeImmutable('+7 days'))->format('c'),
        'revoked_at' => '2025-01-01T00:00:00+00:00',
    ];

    $this->hashService->shouldReceive('hash')->once()->andReturn('hashed-token');
    $this->refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($record);
    $this->refreshTokenRepository->shouldReceive('revokeByFamily')->once()->with('family-123');

    $this->useCase->execute(new RefreshTokenInput('reused-token'));
})->throws(AuthenticationException::class, 'Token reuse detected');

it('throws on invalid refresh token', function () {
    $this->hashService->shouldReceive('hash')->once()->andReturn('unknown-hash');
    $this->refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn(null);

    $this->useCase->execute(new RefreshTokenInput('invalid-token'));
})->throws(AuthenticationException::class, 'Invalid refresh token');

it('throws on expired refresh token', function () {
    $record = [
        'id' => 'token-id',
        'user_id' => $this->userId,
        'token_hash' => 'hashed-token',
        'family_id' => 'family-123',
        'expires_at' => (new DateTimeImmutable('-1 day'))->format('c'),
        'revoked_at' => null,
    ];

    $this->hashService->shouldReceive('hash')->once()->andReturn('hashed-token');
    $this->refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn($record);

    $this->useCase->execute(new RefreshTokenInput('expired-token'));
})->throws(AuthenticationException::class, 'Refresh token expired');
