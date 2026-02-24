<?php

declare(strict_types=1);

use App\Domain\Identity\ValueObjects\TwoFactorSecret;

it('creates a two factor secret', function () {
    $secret = new TwoFactorSecret('encrypted-secret-value');

    expect($secret->encryptedValue)->toBe('encrypted-secret-value');
});

it('compares two equal secrets', function () {
    $secret1 = new TwoFactorSecret('same-value');
    $secret2 = new TwoFactorSecret('same-value');

    expect($secret1->equals($secret2))->toBeTrue();
});

it('compares two different secrets', function () {
    $secret1 = new TwoFactorSecret('value-a');
    $secret2 = new TwoFactorSecret('value-b');

    expect($secret1->equals($secret2))->toBeFalse();
});

it('converts to string', function () {
    $secret = new TwoFactorSecret('encrypted-value');

    expect((string) $secret)->toBe('encrypted-value');
});
