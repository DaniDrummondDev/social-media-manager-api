<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\ChangePasswordInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\UseCases\ChangePasswordUseCase;
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
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->passwordPolicy = new PasswordPolicyService;
    $this->refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new ChangePasswordUseCase(
        $this->userRepository,
        $this->passwordPolicy,
        $this->refreshTokenRepository,
        $this->eventDispatcher,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
    $this->currentPassword = 'SecureP@ss1';

    $this->user = User::reconstitute(
        id: Uuid::fromString($this->userId),
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText($this->currentPassword),
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

it('changes password and revokes tokens', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->userRepository->shouldReceive('update')->once();
    $this->refreshTokenRepository->shouldReceive('revokeAllForUser')->once()->with($this->userId);
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new ChangePasswordInput($this->userId, $this->currentPassword, 'NewSecureP@ss2', 'NewSecureP@ss2');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(MessageOutput::class)
        ->and($output->message)->toBe('Password changed successfully');
});

it('throws on invalid current password', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);

    $input = new ChangePasswordInput($this->userId, 'WrongP@ss1', 'NewSecureP@ss2', 'NewSecureP@ss2');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class, 'Invalid current password');

it('throws when new password is weak', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);

    $input = new ChangePasswordInput($this->userId, $this->currentPassword, 'weak', 'weak');
    $this->useCase->execute($input);
})->throws(WeakPasswordException::class);

it('throws when user not found', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new ChangePasswordInput($this->userId, $this->currentPassword, 'NewSecureP@ss2', 'NewSecureP@ss2');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class);
