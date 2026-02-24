<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class LoginInput
{
    public function __construct(
        public string $email,
        public string $password,
        public string $ipAddress,
        public string $userAgent,
    ) {}
}
