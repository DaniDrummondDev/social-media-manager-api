<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

use App\Domain\Organization\Entities\Organization;

final readonly class OrganizationOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $timezone,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Organization $organization): self
    {
        return new self(
            id: (string) $organization->id,
            name: $organization->name,
            slug: (string) $organization->slug,
            timezone: $organization->timezone,
            status: $organization->status->value,
            createdAt: $organization->createdAt->format('c'),
            updatedAt: $organization->updatedAt->format('c'),
        );
    }
}
