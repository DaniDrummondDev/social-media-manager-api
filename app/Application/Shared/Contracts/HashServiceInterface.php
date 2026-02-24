<?php

declare(strict_types=1);

namespace App\Application\Shared\Contracts;

interface HashServiceInterface
{
    public function hash(string $value): string;

    public function verify(string $value, string $hash): bool;
}
