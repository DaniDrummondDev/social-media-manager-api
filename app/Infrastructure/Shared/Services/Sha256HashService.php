<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Services;

use App\Application\Shared\Contracts\HashServiceInterface;

final class Sha256HashService implements HashServiceInterface
{
    public function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    public function verify(string $value, string $hash): bool
    {
        return hash_equals($hash, hash('sha256', $value));
    }
}
