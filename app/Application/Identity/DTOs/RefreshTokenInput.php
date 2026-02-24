<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class RefreshTokenInput
{
    public function __construct(
        public string $refreshToken,
    ) {}
}
