<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class TwoFactorChallengeOutput
{
    public function __construct(
        public bool $requires2fa,
        public string $tempToken,
    ) {}
}
