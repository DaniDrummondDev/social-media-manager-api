<?php

declare(strict_types=1);

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->repository = app(UserRepositoryInterface::class);
});

it('creates a user and retrieves by id', function () {
    $user = User::create(
        name: 'João Silva',
        email: Email::fromString('joao@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
        timezone: 'America/Sao_Paulo',
        phone: '+5511999999999',
    );

    $this->repository->create($user);

    $found = $this->repository->findById($user->id);

    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('João Silva')
        ->and((string) $found->email)->toBe('joao@example.com')
        ->and($found->phone)->toBe('+5511999999999')
        ->and($found->timezone)->toBe('America/Sao_Paulo')
        ->and($found->status)->toBe(UserStatus::Active)
        ->and($found->twoFactorEnabled)->toBeFalse()
        ->and($found->emailVerifiedAt)->toBeNull();
});

it('returns null when user not found by id', function () {
    $found = $this->repository->findById(Uuid::generate());

    expect($found)->toBeNull();
});

it('finds user by email', function () {
    $user = User::create(
        name: 'Maria',
        email: Email::fromString('maria@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
    );

    $this->repository->create($user);

    $found = $this->repository->findByEmail(Email::fromString('maria@example.com'));

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $user->id);
});

it('returns null when user not found by email', function () {
    $found = $this->repository->findByEmail(Email::fromString('nonexistent@example.com'));

    expect($found)->toBeNull();
});

it('updates user fields', function () {
    $user = User::create(
        name: 'Original',
        email: Email::fromString('update@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
    );

    $this->repository->create($user);

    $updated = $user->updateProfile(name: 'Updated Name', timezone: 'Europe/London');
    $this->repository->update($updated);

    $found = $this->repository->findById($user->id);

    expect($found->name)->toBe('Updated Name')
        ->and($found->timezone)->toBe('Europe/London');
});

it('deletes a user', function () {
    $user = User::create(
        name: 'ToDelete',
        email: Email::fromString('delete@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
    );

    $this->repository->create($user);
    $this->repository->delete($user->id);

    expect($this->repository->findById($user->id))->toBeNull();
});

it('checks if email exists', function () {
    $user = User::create(
        name: 'Exists',
        email: Email::fromString('exists@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
    );

    $this->repository->create($user);

    expect($this->repository->existsByEmail(Email::fromString('exists@example.com')))->toBeTrue()
        ->and($this->repository->existsByEmail(Email::fromString('nope@example.com')))->toBeFalse();
});

it('password survives round-trip without double-hashing', function () {
    $plainPassword = 'SecureP@ss1';
    $user = User::create(
        name: 'Password Test',
        email: Email::fromString('password@example.com'),
        password: HashedPassword::fromPlainText($plainPassword),
    );

    $this->repository->create($user);

    $found = $this->repository->findById($user->id);

    expect(password_verify($plainPassword, (string) $found->password))->toBeTrue();
});
