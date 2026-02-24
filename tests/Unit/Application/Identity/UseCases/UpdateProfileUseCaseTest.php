<?php

declare(strict_types=1);

use App\Application\Identity\DTOs\UpdateProfileInput;
use App\Application\Identity\DTOs\UserOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\UseCases\UpdateProfileUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new UpdateProfileUseCase(
        $this->userRepository,
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

it('updates profile fields', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->userRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new UpdateProfileInput($this->userId, name: 'Jane Doe', timezone: 'UTC');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(UserOutput::class)
        ->and($output->name)->toBe('Jane Doe')
        ->and($output->timezone)->toBe('UTC');
});

it('skips update when no changes', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->userRepository->shouldNotReceive('update');
    $this->eventDispatcher->shouldNotReceive('dispatch');

    $input = new UpdateProfileInput($this->userId);
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(UserOutput::class)
        ->and($output->name)->toBe('John Doe');
});

it('throws when user not found', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new UpdateProfileInput($this->userId, name: 'New Name'));
})->throws(AuthenticationException::class);
