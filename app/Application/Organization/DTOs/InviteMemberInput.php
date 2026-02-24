<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

final readonly class InviteMemberInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $email,
        public string $role,
    ) {}
}
