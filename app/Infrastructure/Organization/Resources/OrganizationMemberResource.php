<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Resources;

use App\Application\Organization\DTOs\OrganizationMemberOutput;

final readonly class OrganizationMemberResource
{
    private function __construct(
        private string $id,
        private string $organization_id,
        private string $user_id,
        private string $role,
        private ?string $invited_by,
        private string $joined_at,
    ) {}

    public static function fromOutput(OrganizationMemberOutput $output): self
    {
        return new self(
            id: $output->id,
            organization_id: $output->organizationId,
            user_id: $output->userId,
            role: $output->role,
            invited_by: $output->invitedBy,
            joined_at: $output->joinedAt,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'role' => $this->role,
            'invited_by' => $this->invited_by,
            'joined_at' => $this->joined_at,
        ];
    }
}
