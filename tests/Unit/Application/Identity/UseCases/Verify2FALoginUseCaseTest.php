<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use App\Application\Identity\DTOs\AuthTokensOutput;
use App\Application\Identity\DTOs\Verify2FAInput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\UseCases\Verify2FALoginUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Exceptions\InvalidTwoFactorCodeException;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\TwoFactorSecret;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->authTokenService = Mockery::mock(AuthTokenServiceInterface::class);
    $this->refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
    $this->twoFactorService = Mockery::mock(TwoFactorServiceInterface::class);
    $this->hashService = Mockery::mock(HashServiceInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new Verify2FALoginUseCase(
        $this->userRepository,
        $this->authTokenService,
        $this->refreshTokenRepository,
        $this->twoFactorService,
        $this->hashService,
        $this->eventDispatcher,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';

    $this->user = User::reconstitute(
        id: Uuid::fromString($this->userId),
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
        phone: null,
        timezone: 'America/Sao_Paulo',
        emailVerifiedAt: new DateTimeImmutable,
        twoFactorEnabled: true,
        twoFactorSecret: new TwoFactorSecret('encrypted-secret'),
        recoveryCodes: null,
        status: UserStatus::Active,
        lastLoginAt: null,
        lastLoginIp: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
});

it('verifies 2FA and returns tokens', function () {
    $this->authTokenService->shouldReceive('validateTempToken')->once()->andReturn($this->userId);
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->twoFactorService->shouldReceive('decryptSecret')->once()->andReturn('plain-secret');
    $this->twoFactorService->shouldReceive('verifyCode')->once()->with('plain-secret', '123456')->andReturn(true);
    $this->authTokenService->shouldReceive('generateAccessToken')->once()->andReturn([
        'token' => 'access-token',
        'jti' => 'jti-123',
        'expires_in' => 900,
    ]);
    $this->authTokenService->shouldReceive('generateRefreshToken')->once()->andReturn('refresh-token');
    $this->hashService->shouldReceive('hash')->once()->andReturn('hashed-refresh');
    $this->refreshTokenRepository->shouldReceive('store')->once();
    $this->userRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new Verify2FAInput('temp-token', '123456', '127.0.0.1', 'TestAgent');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(AuthTokensOutput::class)
        ->and($output->accessToken)->toBe('access-token')
        ->and($output->refreshToken)->toBe('refresh-token');
});

it('throws on invalid temp token', function () {
    $this->authTokenService->shouldReceive('validateTempToken')->once()->andReturn(null);

    $input = new Verify2FAInput('invalid-temp', '123456', '127.0.0.1', 'TestAgent');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class);

it('throws on invalid OTP code', function () {
    $this->authTokenService->shouldReceive('validateTempToken')->once()->andReturn($this->userId);
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->twoFactorService->shouldReceive('decryptSecret')->once()->andReturn('plain-secret');
    $this->twoFactorService->shouldReceive('verifyCode')->once()->andReturn(false);

    $input = new Verify2FAInput('temp-token', '000000', '127.0.0.1', 'TestAgent');
    $this->useCase->execute($input);
})->throws(InvalidTwoFactorCodeException::class);

it('throws when user not found', function () {
    $this->authTokenService->shouldReceive('validateTempToken')->once()->andReturn($this->userId);
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new Verify2FAInput('temp-token', '123456', '127.0.0.1', 'TestAgent');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class);
