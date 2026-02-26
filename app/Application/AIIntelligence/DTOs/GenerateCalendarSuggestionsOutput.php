<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateCalendarSuggestionsOutput
{
    public function __construct(
        public string $suggestionId,
        public string $status,
        public string $message,
    ) {}
}
