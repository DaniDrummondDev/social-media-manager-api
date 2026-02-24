<?php

declare(strict_types=1);

use App\Domain\Identity\Exceptions\WeakPasswordException;
use App\Domain\Identity\Services\PasswordPolicyService;

beforeEach(function () {
    $this->service = new PasswordPolicyService;
});

it('accepts a valid password', function () {
    $this->service->validate('SecureP@ss1');

    expect(true)->toBeTrue();
});

it('rejects password shorter than 8 characters', function () {
    $this->service->validate('Sh0rt!');
})->throws(WeakPasswordException::class, 'minimum 8 characters');

it('rejects password without uppercase letter', function () {
    $this->service->validate('lowercase1!');
})->throws(WeakPasswordException::class, 'uppercase letter');

it('rejects password without lowercase letter', function () {
    $this->service->validate('UPPERCASE1!');
})->throws(WeakPasswordException::class, 'lowercase letter');

it('rejects password without number', function () {
    $this->service->validate('NoNumber!!');
})->throws(WeakPasswordException::class, 'number');

it('rejects password without special character', function () {
    $this->service->validate('NoSpecial1');
})->throws(WeakPasswordException::class, 'special character');

it('rejects password equal to email', function () {
    $this->service->validate('User@test1', 'user@test1');
})->throws(WeakPasswordException::class, 'equal to email');
