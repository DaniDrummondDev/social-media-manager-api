<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\DTOs\VerifyEmailInput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\Exceptions\InvalidTokenException;
use App\Application\Identity\UseCases\VerifyEmailUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->emailVerification = Mockery::mock(EmailVerificationServiceInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new VerifyEmailUseCase(
        $this->userRepository,
        $this->emailVerification,
        $this->eventDispatcher,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
});

it('verifies email successfully', function () {
    $user = User::reconstitute(
        id: Uuid::fromString($this->userId),
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
        phone: null,
        timezone: 'America/Sao_Paulo',
        emailVerifiedAt: null,
        twoFactorEnabled: false,
        twoFactorSecret: null,
        recoveryCodes: null,
        status: UserStatus::Active,
        lastLoginAt: null,
        lastLoginIp: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->emailVerification->shouldReceive('verifyToken')->once()->with('valid-token')->andReturn($this->userId);
    $this->userRepository->shouldReceive('findById')->once()->andReturn($user);
    $this->userRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new VerifyEmailInput('valid-token'));

    expect($output)->toBeInstanceOf(MessageOutput::class)
        ->and($output->message)->toBe('Email verified successfully');
});

it('throws when token is invalid', function () {
    $this->emailVerification->shouldReceive('verifyToken')
        ->once()
        ->andThrow(new InvalidTokenException);

    $this->useCase->execute(new VerifyEmailInput('invalid-token'));
})->throws(InvalidTokenException::class);

it('throws when user not found', function () {
    $this->emailVerification->shouldReceive('verifyToken')->once()->andReturn($this->userId);
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new VerifyEmailInput('valid-token'));
})->throws(AuthenticationException::class);
