<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\CalendarSuggestion;

final readonly class CalendarSuggestionListOutput
{
    public function __construct(
        public string $id,
        public string $periodStart,
        public string $periodEnd,
        public string $status,
        public int $itemCount,
        public string $generatedAt,
        public string $expiresAt,
    ) {}

    public static function fromEntity(CalendarSuggestion $suggestion): self
    {
        return new self(
            id: (string) $suggestion->id,
            periodStart: $suggestion->periodStart->format('Y-m-d'),
            periodEnd: $suggestion->periodEnd->format('Y-m-d'),
            status: $suggestion->status->value,
            itemCount: count($suggestion->suggestions),
            generatedAt: $suggestion->generatedAt->format('c'),
            expiresAt: $suggestion->expiresAt->format('c'),
        );
    }
}
