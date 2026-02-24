<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class UpdateCampaignInput
{
    /**
     * @param  string[]|null  $tags
     */
    public function __construct(
        public string $organizationId,
        public string $campaignId,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        public ?array $tags = null,
        public ?string $status = null,
    ) {}
}
