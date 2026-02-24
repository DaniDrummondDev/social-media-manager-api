<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

final readonly class AcceptInviteInput
{
    public function __construct(
        public string $token,
        public string $userId,
    ) {}
}
