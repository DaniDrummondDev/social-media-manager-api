<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use RuntimeException;

final class CrmTokenEncrypter
{
    private const string CIPHER = 'aes-256-gcm';

    private const int TAG_LENGTH = 16;

    private readonly string $key;

    public function __construct()
    {
        $key = config('crm.encryption.key');

        if (empty($key)) {
            throw new RuntimeException('CRM_TOKEN_ENCRYPTION_KEY is not configured.');
        }

        $this->key = base64_decode($key);

        if (strlen($this->key) !== 32) {
            throw new RuntimeException('CRM_TOKEN_ENCRYPTION_KEY must be 32 bytes (base64 encoded).');
        }
    }

    public function encrypt(string $plainToken): string
    {
        $nonce = random_bytes(openssl_cipher_iv_length(self::CIPHER));

        $ciphertext = openssl_encrypt(
            data: $plainToken,
            cipher_algo: self::CIPHER,
            passphrase: $this->key,
            options: OPENSSL_RAW_DATA,
            iv: $nonce,
            tag: $tag,
            tag_length: self::TAG_LENGTH,
        );

        if ($ciphertext === false) {
            throw new RuntimeException('CRM token encryption failed.');
        }

        return base64_encode($nonce.$ciphertext.$tag);
    }

    public function decrypt(string $encryptedToken): string
    {
        $decoded = base64_decode($encryptedToken, true);

        if ($decoded === false) {
            throw new RuntimeException('CRM token decryption failed: invalid base64.');
        }

        $nonceLength = openssl_cipher_iv_length(self::CIPHER);
        $nonce = substr($decoded, 0, $nonceLength);
        $tag = substr($decoded, -self::TAG_LENGTH);
        $ciphertext = substr($decoded, $nonceLength, -self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            data: $ciphertext,
            cipher_algo: self::CIPHER,
            passphrase: $this->key,
            options: OPENSSL_RAW_DATA,
            iv: $nonce,
            tag: $tag,
        );

        if ($plaintext === false) {
            throw new RuntimeException('CRM token decryption failed: invalid ciphertext or key.');
        }

        return $plaintext;
    }
}
