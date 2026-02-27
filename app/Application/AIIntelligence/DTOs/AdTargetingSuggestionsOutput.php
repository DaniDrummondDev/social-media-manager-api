<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class AdTargetingSuggestionsOutput
{
    /**
     * @param  array<string, mixed>  $suggestions
     */
    public function __construct(
        public array $suggestions,
        public int $suggestionCount,
        public string $basedOnInsightType,
        public string $confidenceLevel,
    ) {}
}
