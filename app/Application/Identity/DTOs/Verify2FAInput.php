<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class Verify2FAInput
{
    public function __construct(
        public string $tempToken,
        public string $otpCode,
        public string $ipAddress,
        public string $userAgent,
    ) {}
}
