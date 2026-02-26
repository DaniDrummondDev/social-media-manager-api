<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class ListAudienceInsightsInput
{
    public function __construct(
        public string $organizationId,
        public ?string $type = null,
        public ?string $socialAccountId = null,
    ) {}
}
