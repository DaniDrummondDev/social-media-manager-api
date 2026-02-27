<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Contracts;

interface AdTokenEncryptorInterface
{
    public function encrypt(string $plainToken): string;

    public function decrypt(string $encryptedToken): string;
}
