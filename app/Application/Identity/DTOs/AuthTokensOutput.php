<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class AuthTokensOutput
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,
        public int $expiresIn,
    ) {}
}
