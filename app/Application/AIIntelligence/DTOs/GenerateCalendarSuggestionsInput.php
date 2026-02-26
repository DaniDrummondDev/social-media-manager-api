<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateCalendarSuggestionsInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $periodStart,
        public string $periodEnd,
    ) {}
}
