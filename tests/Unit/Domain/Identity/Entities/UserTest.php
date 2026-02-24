<?php

declare(strict_types=1);

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Events\TwoFactorDisabled;
use App\Domain\Identity\Events\TwoFactorEnabled;
use App\Domain\Identity\Events\UserEmailVerified;
use App\Domain\Identity\Events\UserLoggedIn;
use App\Domain\Identity\Events\UserPasswordChanged;
use App\Domain\Identity\Events\UserProfileUpdated;
use App\Domain\Identity\Events\UserRegistered;
use App\Domain\Identity\Exceptions\InvalidUserStatusException;
use App\Domain\Identity\Exceptions\UserAlreadyVerifiedException;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\TwoFactorSecret;
use App\Domain\Identity\ValueObjects\UserStatus;

function createTestUser(): User
{
    return User::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
    );
}

it('creates a user with default values', function () {
    $user = createTestUser();

    expect($user->name)->toBe('John Doe')
        ->and((string) $user->email)->toBe('john@example.com')
        ->and($user->timezone)->toBe('America/Sao_Paulo')
        ->and($user->phone)->toBeNull()
        ->and($user->emailVerifiedAt)->toBeNull()
        ->and($user->twoFactorEnabled)->toBeFalse()
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->lastLoginAt)->toBeNull()
        ->and($user->lastLoginIp)->toBeNull();
});

it('records UserRegistered event on creation', function () {
    $user = createTestUser();

    expect($user->domainEvents)->toHaveCount(1)
        ->and($user->domainEvents[0])->toBeInstanceOf(UserRegistered::class)
        ->and($user->domainEvents[0]->email)->toBe('john@example.com');
});

it('reconstitutes a user without events', function () {
    $user = createTestUser();

    $reconstituted = User::reconstitute(
        id: $user->id,
        name: $user->name,
        email: $user->email,
        password: $user->password,
        phone: $user->phone,
        timezone: $user->timezone,
        emailVerifiedAt: $user->emailVerifiedAt,
        twoFactorEnabled: $user->twoFactorEnabled,
        twoFactorSecret: $user->twoFactorSecret,
        recoveryCodes: $user->recoveryCodes,
        status: $user->status,
        lastLoginAt: $user->lastLoginAt,
        lastLoginIp: $user->lastLoginIp,
        createdAt: $user->createdAt,
        updatedAt: $user->updatedAt,
    );

    expect($reconstituted->domainEvents)->toBeEmpty()
        ->and($reconstituted->name)->toBe('John Doe');
});

it('verifies email', function () {
    $user = createTestUser();
    $verified = $user->verifyEmail();

    expect($verified->emailVerifiedAt)->not->toBeNull()
        ->and($verified->isEmailVerified())->toBeTrue()
        ->and($verified->domainEvents)->toHaveCount(2)
        ->and($verified->domainEvents[1])->toBeInstanceOf(UserEmailVerified::class);
});

it('throws when verifying already verified email', function () {
    $user = createTestUser()->verifyEmail();
    $user->verifyEmail();
})->throws(UserAlreadyVerifiedException::class);

it('changes password', function () {
    $user = createTestUser();
    $newPassword = HashedPassword::fromPlainText('NewSecure@1');
    $updated = $user->changePassword($newPassword);

    expect($updated->password->verify('NewSecure@1'))->toBeTrue()
        ->and($updated->domainEvents)->toHaveCount(2)
        ->and($updated->domainEvents[1])->toBeInstanceOf(UserPasswordChanged::class);
});

it('updates profile', function () {
    $user = createTestUser();
    $updated = $user->updateProfile(name: 'Jane Doe', timezone: 'UTC');

    expect($updated->name)->toBe('Jane Doe')
        ->and($updated->timezone)->toBe('UTC')
        ->and($updated->domainEvents)->toHaveCount(2)
        ->and($updated->domainEvents[1])->toBeInstanceOf(UserProfileUpdated::class)
        ->and($updated->domainEvents[1]->changes)->toHaveKeys(['name', 'timezone']);
});

it('returns same instance when profile has no changes', function () {
    $user = createTestUser();
    $updated = $user->updateProfile(name: 'John Doe');

    expect($updated)->toBe($user);
});

it('records login', function () {
    $user = createTestUser();
    $loggedIn = $user->recordLogin('192.168.1.1', 'Mozilla/5.0', 'org-id-123');

    expect($loggedIn->lastLoginIp)->toBe('192.168.1.1')
        ->and($loggedIn->lastLoginAt)->not->toBeNull()
        ->and($loggedIn->domainEvents)->toHaveCount(2)
        ->and($loggedIn->domainEvents[1])->toBeInstanceOf(UserLoggedIn::class)
        ->and($loggedIn->domainEvents[1]->ipAddress)->toBe('192.168.1.1');
});

it('enables two factor authentication', function () {
    $user = createTestUser();
    $secret = new TwoFactorSecret('encrypted-secret');
    $updated = $user->enableTwoFactor($secret, 'recovery-codes');

    expect($updated->twoFactorEnabled)->toBeTrue()
        ->and($updated->twoFactorSecret->encryptedValue)->toBe('encrypted-secret')
        ->and($updated->recoveryCodes)->toBe('recovery-codes')
        ->and($updated->domainEvents)->toHaveCount(2)
        ->and($updated->domainEvents[1])->toBeInstanceOf(TwoFactorEnabled::class);
});

it('disables two factor authentication', function () {
    $user = createTestUser();
    $secret = new TwoFactorSecret('encrypted-secret');
    $withTwoFactor = $user->enableTwoFactor($secret, 'recovery-codes');
    $disabled = $withTwoFactor->disableTwoFactor();

    expect($disabled->twoFactorEnabled)->toBeFalse()
        ->and($disabled->twoFactorSecret)->toBeNull()
        ->and($disabled->recoveryCodes)->toBeNull()
        ->and($disabled->domainEvents)->toHaveCount(3)
        ->and($disabled->domainEvents[2])->toBeInstanceOf(TwoFactorDisabled::class);
});

it('changes status with valid transition', function () {
    $user = createTestUser();
    $suspended = $user->changeStatus(UserStatus::Suspended);

    expect($suspended->status)->toBe(UserStatus::Suspended);
});

it('throws on invalid status transition', function () {
    $user = createTestUser()->changeStatus(UserStatus::Inactive);
    $user->changeStatus(UserStatus::Suspended);
})->throws(InvalidUserStatusException::class);

it('releases domain events', function () {
    $user = createTestUser();
    expect($user->domainEvents)->toHaveCount(1);

    $released = $user->releaseEvents();
    expect($released->domainEvents)->toBeEmpty()
        ->and($released->name)->toBe('John Doe');
});
