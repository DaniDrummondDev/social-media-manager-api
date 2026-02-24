<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\DTOs\ChangeEmailInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\Exceptions\EmailAlreadyInUseException;
use App\Application\Identity\UseCases\ChangeEmailUseCase;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->emailVerification = Mockery::mock(EmailVerificationServiceInterface::class);

    $this->useCase = new ChangeEmailUseCase(
        $this->userRepository,
        $this->emailVerification,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
    $this->password = 'SecureP@ss1';

    $this->user = User::reconstitute(
        id: Uuid::fromString($this->userId),
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText($this->password),
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

it('sends verification email to new address', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->userRepository->shouldReceive('existsByEmail')->once()->andReturn(false);
    $this->emailVerification->shouldReceive('sendVerificationEmail')->once()
        ->with($this->userId, 'new@example.com');

    $output = $this->useCase->execute(new ChangeEmailInput($this->userId, 'new@example.com', $this->password));

    expect($output)->toBeInstanceOf(MessageOutput::class)
        ->and($output->message)->toContain('new@example.com');
});

it('throws on invalid password', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);

    $this->useCase->execute(new ChangeEmailInput($this->userId, 'new@example.com', 'WrongP@ss1'));
})->throws(AuthenticationException::class, 'Invalid password');

it('throws when new email already in use', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->userRepository->shouldReceive('existsByEmail')->once()->andReturn(true);

    $this->useCase->execute(new ChangeEmailInput($this->userId, 'taken@example.com', $this->password));
})->throws(EmailAlreadyInUseException::class);

it('throws when user not found', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new ChangeEmailInput($this->userId, 'new@example.com', $this->password));
})->throws(AuthenticationException::class);
