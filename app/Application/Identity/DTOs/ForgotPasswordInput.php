<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class ForgotPasswordInput
{
    public function __construct(
        public string $email,
    ) {}
}
