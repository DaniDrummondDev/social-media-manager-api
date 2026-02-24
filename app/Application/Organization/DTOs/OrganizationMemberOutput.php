<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

use App\Domain\Organization\Entities\OrganizationMember;

final readonly class OrganizationMemberOutput
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $userId,
        public string $role,
        public ?string $invitedBy,
        public string $joinedAt,
    ) {}

    public static function fromEntity(OrganizationMember $member): self
    {
        return new self(
            id: (string) $member->id,
            organizationId: (string) $member->organizationId,
            userId: (string) $member->userId,
            role: $member->role->value,
            invitedBy: $member->invitedBy ? (string) $member->invitedBy : null,
            joinedAt: $member->joinedAt->format('c'),
        );
    }
}
