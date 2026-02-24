<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class ChangePasswordInput
{
    public function __construct(
        public string $userId,
        public string $currentPassword,
        public string $newPassword,
        public string $newPasswordConfirmation,
    ) {}
}
