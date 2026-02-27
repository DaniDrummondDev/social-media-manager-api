<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;

beforeEach(function () {
    $this->encrypter = app(AdTokenEncryptorInterface::class);
});

it('encrypts and decrypts token preserving plaintext', function () {
    $plaintext = 'meta_access_token_abc123';
    $encrypted = $this->encrypter->encrypt($plaintext);
    $decrypted = $this->encrypter->decrypt($encrypted);

    expect($decrypted)->toBe($plaintext)
        ->and($encrypted)->not->toBe($plaintext);
});

it('generates different ciphertext for same plaintext', function () {
    $plaintext = 'same_token_value';
    $encrypted1 = $this->encrypter->encrypt($plaintext);
    $encrypted2 = $this->encrypter->encrypt($plaintext);

    expect($encrypted1)->not->toBe($encrypted2);
});

it('decrypts with invalid base64 throws RuntimeException', function () {
    $this->encrypter->decrypt('not-valid-base64!!!');
})->throws(RuntimeException::class);

it('decrypts with corrupted ciphertext throws RuntimeException', function () {
    $encrypted = $this->encrypter->encrypt('test-token');
    $corrupted = substr($encrypted, 0, -5).'AAAAA';

    $this->encrypter->decrypt($corrupted);
})->throws(RuntimeException::class);

it('handles long tokens correctly', function () {
    $longToken = str_repeat('a', 2000);
    $encrypted = $this->encrypter->encrypt($longToken);
    $decrypted = $this->encrypter->decrypt($encrypted);

    expect($decrypted)->toBe($longToken);
});
