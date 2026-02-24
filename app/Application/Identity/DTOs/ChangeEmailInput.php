<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class ChangeEmailInput
{
    public function __construct(
        public string $userId,
        public string $newEmail,
        public string $password,
    ) {}
}
