<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

final readonly class ChangeMemberRoleInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $targetUserId,
        public string $newRole,
    ) {}
}
