<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class ResetPasswordInput
{
    public function __construct(
        public string $userId,
    ) {}
}
