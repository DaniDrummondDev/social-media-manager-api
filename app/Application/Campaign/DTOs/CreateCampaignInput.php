<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class CreateCampaignInput
{
    /**
     * @param  string[]  $tags
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $name,
        public ?string $description = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        public array $tags = [],
        public ?string $briefText = null,
        public ?string $briefTargetAudience = null,
        public ?string $briefRestrictions = null,
        public ?string $briefCta = null,
    ) {}
}
