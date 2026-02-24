<?php

declare(strict_types=1);

use App\Infrastructure\SocialAccount\Services\SocialTokenEncrypter;

beforeEach(function () {
    $this->encrypter = app(SocialTokenEncrypter::class);
});

it('encrypts and decrypts roundtrip', function () {
    $plain = 'ya29.a0AfB_byC_super_secret_access_token_12345';

    $encrypted = $this->encrypter->encrypt($plain);
    $decrypted = $this->encrypter->decrypt($encrypted);

    expect($decrypted)->toBe($plain)
        ->and($encrypted)->not->toBe($plain);
});

it('produces different ciphertext each time', function () {
    $plain = 'same-token';

    $encrypted1 = $this->encrypter->encrypt($plain);
    $encrypted2 = $this->encrypter->encrypt($plain);

    expect($encrypted1)->not->toBe($encrypted2);

    // Both decrypt to the same value
    expect($this->encrypter->decrypt($encrypted1))->toBe($plain)
        ->and($this->encrypter->decrypt($encrypted2))->toBe($plain);
});

it('fails decryption with tampered data', function () {
    $encrypted = $this->encrypter->encrypt('my-token');

    // Tamper with the ciphertext
    $tampered = $encrypted.'TAMPERED';

    $this->encrypter->decrypt($tampered);
})->throws(RuntimeException::class);

it('fails on invalid base64 input', function () {
    $this->encrypter->decrypt('!!!not-base64!!!');
})->throws(RuntimeException::class);

it('fails when encryption key is missing', function () {
    config(['social-media.encryption.key' => null]);

    new SocialTokenEncrypter;
})->throws(RuntimeException::class, 'SOCIAL_TOKEN_ENCRYPTION_KEY is not configured');
