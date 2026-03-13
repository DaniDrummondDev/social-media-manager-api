<?php

declare(strict_types=1);

use App\Domain\Identity\ValueObjects\HashedPassword;

it('creates a hashed password from plain text using argon2id', function () {
    $password = HashedPassword::fromPlainText('SecurePass123!');

    expect($password->hash)->not->toBe('SecurePass123!')
        ->and($password->hash)->toStartWith('$argon2id$');
});

it('verifies correct password', function () {
    $password = HashedPassword::fromPlainText('SecurePass123!');

    expect($password->verify('SecurePass123!'))->toBeTrue();
});

it('rejects wrong password', function () {
    $password = HashedPassword::fromPlainText('SecurePass123!');

    expect($password->verify('WrongPassword'))->toBeFalse();
});

it('creates from existing hash', function () {
    $original = HashedPassword::fromPlainText('SecurePass123!');
    $reconstituted = HashedPassword::fromHash($original->hash);

    expect($reconstituted->verify('SecurePass123!'))->toBeTrue();
});

it('converts to string as hash', function () {
    $password = HashedPassword::fromPlainText('SecurePass123!');

    expect((string) $password)->toBe($password->hash);
});

it('does not need rehash for current argon2id hash', function () {
    $password = HashedPassword::fromPlainText('SecurePass123!');

    expect($password->needsRehash())->toBeFalse();
});

it('needs rehash for legacy bcrypt hash', function () {
    $bcryptHash = password_hash('SecurePass123!', PASSWORD_BCRYPT, ['cost' => 12]);
    $password = HashedPassword::fromHash($bcryptHash);

    expect($password->needsRehash())->toBeTrue()
        ->and($password->verify('SecurePass123!'))->toBeTrue();
});
