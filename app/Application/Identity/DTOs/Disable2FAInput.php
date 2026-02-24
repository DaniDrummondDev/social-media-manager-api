<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class Disable2FAInput
{
    public function __construct(
        public string $userId,
        public string $password,
    ) {}
}
