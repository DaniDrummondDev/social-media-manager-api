<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

use App\Domain\Campaign\Entities\Campaign;

final readonly class CampaignOutput
{
    /**
     * @param  string[]  $tags
     * @param  array<string, int>|null  $stats
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $description,
        public ?string $startsAt,
        public ?string $endsAt,
        public string $status,
        public array $tags,
        public ?array $stats,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * @param  array<string, int>|null  $stats
     */
    public static function fromEntity(Campaign $campaign, ?array $stats = null): self
    {
        return new self(
            id: (string) $campaign->id,
            organizationId: (string) $campaign->organizationId,
            name: $campaign->name,
            description: $campaign->description,
            startsAt: $campaign->startsAt?->format('c'),
            endsAt: $campaign->endsAt?->format('c'),
            status: $campaign->status->value,
            tags: $campaign->tags,
            stats: $stats,
            createdAt: $campaign->createdAt->format('c'),
            updatedAt: $campaign->updatedAt->format('c'),
        );
    }
}
