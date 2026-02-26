<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class ListSafetyRulesInput
{
    public function __construct(
        public string $organizationId,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
