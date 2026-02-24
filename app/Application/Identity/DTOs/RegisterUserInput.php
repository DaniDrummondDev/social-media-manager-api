<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class RegisterUserInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $passwordConfirmation,
    ) {}
}
