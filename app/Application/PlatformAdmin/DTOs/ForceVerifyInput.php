<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class ForceVerifyInput
{
    public function __construct(
        public string $userId,
    ) {}
}
