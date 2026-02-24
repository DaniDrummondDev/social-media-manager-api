<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\AuthTokensOutput;
use App\Application\Identity\DTOs\LoginInput;
use App\Application\Identity\DTOs\TwoFactorChallengeOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\UseCases\LoginUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Domain\Identity\Entities\User;
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
    $this->hashService = Mockery::mock(HashServiceInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new LoginUseCase(
        $this->userRepository,
        $this->authTokenService,
        $this->refreshTokenRepository,
        $this->hashService,
        $this->eventDispatcher,
    );

    $this->password = 'SecureP@ss1';
});

function createLoginUser(string $password, bool $twoFactor = false, UserStatus $status = UserStatus::Active): User
{
    return User::reconstitute(
        id: Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'),
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText($password),
        phone: null,
        timezone: 'America/Sao_Paulo',
        emailVerifiedAt: new DateTimeImmutable,
        twoFactorEnabled: $twoFactor,
        twoFactorSecret: $twoFactor ? new TwoFactorSecret('encrypted-secret') : null,
        recoveryCodes: null,
        status: $status,
        lastLoginAt: null,
        lastLoginIp: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('returns tokens for user without 2FA', function () {
    $user = createLoginUser($this->password);

    $this->userRepository->shouldReceive('findByEmail')->once()->andReturn($user);
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

    $input = new LoginInput('john@example.com', $this->password, '127.0.0.1', 'TestAgent');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(AuthTokensOutput::class)
        ->and($output->accessToken)->toBe('access-token')
        ->and($output->refreshToken)->toBe('refresh-token')
        ->and($output->tokenType)->toBe('Bearer')
        ->and($output->expiresIn)->toBe(900);
});

it('returns 2FA challenge when 2FA is enabled', function () {
    $user = createLoginUser($this->password, twoFactor: true);

    $this->userRepository->shouldReceive('findByEmail')->once()->andReturn($user);
    $this->authTokenService->shouldReceive('generateTempToken')->once()->andReturn([
        'token' => 'temp-token',
        'expires_in' => 300,
    ]);

    $input = new LoginInput('john@example.com', $this->password, '127.0.0.1', 'TestAgent');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(TwoFactorChallengeOutput::class)
        ->and($output->requires2fa)->toBeTrue()
        ->and($output->tempToken)->toBe('temp-token');
});

it('throws on invalid password', function () {
    $user = createLoginUser($this->password);

    $this->userRepository->shouldReceive('findByEmail')->once()->andReturn($user);

    $input = new LoginInput('john@example.com', 'WrongP@ss1', '127.0.0.1', 'TestAgent');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class);

it('throws when user not found', function () {
    $this->userRepository->shouldReceive('findByEmail')->once()->andReturn(null);

    $input = new LoginInput('unknown@example.com', 'SecureP@ss1', '127.0.0.1', 'TestAgent');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class);

it('throws when account is inactive', function () {
    $user = createLoginUser($this->password, status: UserStatus::Inactive);

    $this->userRepository->shouldReceive('findByEmail')->once()->andReturn($user);

    $input = new LoginInput('john@example.com', $this->password, '127.0.0.1', 'TestAgent');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class);
