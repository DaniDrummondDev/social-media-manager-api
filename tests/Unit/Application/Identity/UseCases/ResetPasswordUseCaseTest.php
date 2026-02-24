<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\PasswordResetServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\DTOs\ResetPasswordInput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\Exceptions\InvalidTokenException;
use App\Application\Identity\UseCases\ResetPasswordUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Exceptions\WeakPasswordException;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\Services\PasswordPolicyService;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->passwordResetService = Mockery::mock(PasswordResetServiceInterface::class);
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->passwordPolicy = new PasswordPolicyService;
    $this->refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new ResetPasswordUseCase(
        $this->passwordResetService,
        $this->userRepository,
        $this->passwordPolicy,
        $this->refreshTokenRepository,
        $this->eventDispatcher,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
});

function createResetUser(string $userId): User
{
    return User::reconstitute(
        id: Uuid::fromString($userId),
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText('OldP@ssw0rd'),
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
}

it('resets password successfully', function () {
    $this->passwordResetService->shouldReceive('verifyToken')->once()->andReturn($this->userId);
    $this->userRepository->shouldReceive('findById')->once()->andReturn(createResetUser($this->userId));
    $this->userRepository->shouldReceive('update')->once();
    $this->passwordResetService->shouldReceive('invalidateTokensForUser')->once()->with($this->userId);
    $this->refreshTokenRepository->shouldReceive('revokeAllForUser')->once()->with($this->userId);
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new ResetPasswordInput('valid-token', 'NewSecureP@ss1', 'NewSecureP@ss1');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(MessageOutput::class)
        ->and($output->message)->toBe('Password reset successfully');
});

it('throws on invalid token', function () {
    $this->passwordResetService->shouldReceive('verifyToken')
        ->once()
        ->andThrow(new InvalidTokenException);

    $input = new ResetPasswordInput('invalid-token', 'NewSecureP@ss1', 'NewSecureP@ss1');
    $this->useCase->execute($input);
})->throws(InvalidTokenException::class);

it('throws when user not found', function () {
    $this->passwordResetService->shouldReceive('verifyToken')->once()->andReturn($this->userId);
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new ResetPasswordInput('valid-token', 'NewSecureP@ss1', 'NewSecureP@ss1');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class);

it('throws when new password is weak', function () {
    $this->passwordResetService->shouldReceive('verifyToken')->once()->andReturn($this->userId);
    $this->userRepository->shouldReceive('findById')->once()->andReturn(createResetUser($this->userId));

    $input = new ResetPasswordInput('valid-token', 'weak', 'weak');
    $this->useCase->execute($input);
})->throws(WeakPasswordException::class);
