<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class DuplicateCampaignInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $campaignId,
        public ?string $name = null,
    ) {}
}
