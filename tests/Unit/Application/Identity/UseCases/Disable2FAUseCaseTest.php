<?php

declare(strict_types=1);

use App\Application\Identity\DTOs\Disable2FAInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\UseCases\Disable2FAUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\TwoFactorSecret;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new Disable2FAUseCase(
        $this->userRepository,
        $this->eventDispatcher,
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
        twoFactorEnabled: true,
        twoFactorSecret: new TwoFactorSecret('encrypted-secret'),
        recoveryCodes: '["code1","code2"]',
        status: UserStatus::Active,
        lastLoginAt: null,
        lastLoginIp: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
});

it('disables 2FA successfully', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->userRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new Disable2FAInput($this->userId, $this->password));

    expect($output)->toBeInstanceOf(MessageOutput::class)
        ->and($output->message)->toBe('Two-factor authentication disabled');
});

it('throws on invalid password', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);

    $this->useCase->execute(new Disable2FAInput($this->userId, 'WrongP@ss1'));
})->throws(AuthenticationException::class, 'Invalid password');

it('throws when user not found', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new Disable2FAInput($this->userId, $this->password));
})->throws(AuthenticationException::class);
