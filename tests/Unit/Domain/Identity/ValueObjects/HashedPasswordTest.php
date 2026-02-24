<?php

declare(strict_types=1);

use App\Domain\Identity\ValueObjects\HashedPassword;

it('creates a hashed password from plain text', function () {
    $password = HashedPassword::fromPlainText('SecurePass123!');

    expect($password->hash)->not->toBe('SecurePass123!')
        ->and($password->hash)->toStartWith('$2y$12$');
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
