<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\CalendarSuggestion;

final readonly class CalendarSuggestionOutput
{
    /**
     * @param  array<array<string, mixed>>  $suggestions
     * @param  array<string, mixed>  $basedOn
     * @param  array<int>|null  $acceptedItems
     */
    public function __construct(
        public string $id,
        public string $periodStart,
        public string $periodEnd,
        public string $status,
        public array $suggestions,
        public array $basedOn,
        public ?array $acceptedItems,
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
            suggestions: array_map(fn ($item) => $item->toArray(), $suggestion->suggestions),
            basedOn: $suggestion->basedOn,
            acceptedItems: $suggestion->acceptedItems,
            generatedAt: $suggestion->generatedAt->format('c'),
            expiresAt: $suggestion->expiresAt->format('c'),
        );
    }
}
