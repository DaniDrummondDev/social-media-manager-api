<?php

declare(strict_types=1);

use App\Domain\SocialAccount\ValueObjects\EncryptedToken;

it('creates from encrypted string', function () {
    $token = EncryptedToken::fromEncrypted('abc123encrypted');

    expect($token->value)->toBe('abc123encrypted')
        ->and((string) $token)->toBe('abc123encrypted');
});

it('detects empty token', function () {
    $empty = EncryptedToken::fromEncrypted('');
    $nonEmpty = EncryptedToken::fromEncrypted('token');

    expect($empty->isEmpty())->toBeTrue()
        ->and($nonEmpty->isEmpty())->toBeFalse();
});

it('compares equality', function () {
    $a = EncryptedToken::fromEncrypted('same');
    $b = EncryptedToken::fromEncrypted('same');
    $c = EncryptedToken::fromEncrypted('different');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
