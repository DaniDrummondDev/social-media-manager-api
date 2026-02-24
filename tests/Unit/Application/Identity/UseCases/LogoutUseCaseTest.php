<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\UseCases\LogoutUseCase;
use App\Application\Shared\Contracts\HashServiceInterface;

beforeEach(function () {
    $this->authTokenService = Mockery::mock(AuthTokenServiceInterface::class);
    $this->refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
    $this->hashService = Mockery::mock(HashServiceInterface::class);

    $this->useCase = new LogoutUseCase(
        $this->authTokenService,
        $this->refreshTokenRepository,
        $this->hashService,
    );
});

it('blacklists access token', function () {
    $this->authTokenService->shouldReceive('blacklistToken')->once()->with('jti-123', 900);

    $this->useCase->execute('jti-123', 900);
});

it('revokes refresh token when provided', function () {
    $this->authTokenService->shouldReceive('blacklistToken')->once();
    $this->hashService->shouldReceive('hash')->once()->with('refresh-token')->andReturn('hashed');
    $this->refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn([
        'id' => 'token-id',
        'user_id' => 'user-123',
        'token_hash' => 'hashed',
        'family_id' => 'family-123',
        'expires_at' => (new DateTimeImmutable('+7 days'))->format('c'),
        'revoked_at' => null,
    ]);
    $this->refreshTokenRepository->shouldReceive('revokeById')->once()->with('token-id');

    $this->useCase->execute('jti-123', 900, 'refresh-token');
});

it('revokes all sessions when requested', function () {
    $this->authTokenService->shouldReceive('blacklistToken')->once();
    $this->refreshTokenRepository->shouldReceive('revokeAllForUser')->once()->with('user-123');

    $this->useCase->execute('jti-123', 900, null, true, 'user-123');
});

it('handles refresh token not found gracefully', function () {
    $this->authTokenService->shouldReceive('blacklistToken')->once();
    $this->hashService->shouldReceive('hash')->once()->andReturn('hashed');
    $this->refreshTokenRepository->shouldReceive('findByTokenHash')->once()->andReturn(null);

    $this->useCase->execute('jti-123', 900, 'unknown-refresh-token');
});
