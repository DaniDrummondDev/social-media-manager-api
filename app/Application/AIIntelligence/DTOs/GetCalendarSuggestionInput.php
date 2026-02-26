<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GetCalendarSuggestionInput
{
    public function __construct(
        public string $organizationId,
        public string $suggestionId,
    ) {}
}
