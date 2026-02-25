<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class BanUserInput
{
    public function __construct(
        public string $userId,
        public string $reason,
    ) {}
}
