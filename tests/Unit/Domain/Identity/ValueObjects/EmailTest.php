<?php

declare(strict_types=1);

use App\Domain\Identity\Exceptions\InvalidEmailException;
use App\Domain\Identity\ValueObjects\Email;

it('creates an email from valid string', function () {
    $email = Email::fromString('user@example.com');

    expect((string) $email)->toBe('user@example.com');
});

it('normalizes email to lowercase', function () {
    $email = Email::fromString('User@EXAMPLE.COM');

    expect((string) $email)->toBe('user@example.com');
});

it('trims whitespace from email', function () {
    $email = Email::fromString('  user@example.com  ');

    expect((string) $email)->toBe('user@example.com');
});

it('rejects invalid email format', function () {
    Email::fromString('not-an-email');
})->throws(InvalidEmailException::class);

it('rejects empty email', function () {
    Email::fromString('');
})->throws(InvalidEmailException::class);

it('extracts domain from email', function () {
    $email = Email::fromString('user@example.com');

    expect($email->domain())->toBe('example.com');
});

it('compares two equal emails', function () {
    $email1 = Email::fromString('user@example.com');
    $email2 = Email::fromString('USER@example.com');

    expect($email1->equals($email2))->toBeTrue();
});

it('compares two different emails', function () {
    $email1 = Email::fromString('user@example.com');
    $email2 = Email::fromString('other@example.com');

    expect($email1->equals($email2))->toBeFalse();
});
