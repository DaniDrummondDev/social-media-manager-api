<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface TwoFactorServiceInterface
{
    public function generateSecret(): string;

    public function generateQrCodeUri(string $secret, string $email): string;

    public function generateQrCodeSvg(string $uri): string;

    public function verifyCode(string $secret, string $code): bool;

    /**
     * @return string[] Array of plaintext recovery codes
     */
    public function generateRecoveryCodes(): array;

    public function encryptSecret(string $plainSecret): string;

    public function decryptSecret(string $encryptedSecret): string;
}
