<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class VerifyEmailInput
{
    public function __construct(
        public string $token,
    ) {}
}
