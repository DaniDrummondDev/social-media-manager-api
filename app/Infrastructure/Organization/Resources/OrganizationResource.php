<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Resources;

use App\Application\Organization\DTOs\OrganizationOutput;

final readonly class OrganizationResource
{
    private function __construct(
        private string $id,
        private string $name,
        private string $slug,
        private string $timezone,
        private string $status,
        private string $created_at,
        private string $updated_at,
    ) {}

    public static function fromOutput(OrganizationOutput $output): self
    {
        return new self(
            id: $output->id,
            name: $output->name,
            slug: $output->slug,
            timezone: $output->timezone,
            status: $output->status,
            created_at: $output->createdAt,
            updated_at: $output->updatedAt,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'timezone' => $this->timezone,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
