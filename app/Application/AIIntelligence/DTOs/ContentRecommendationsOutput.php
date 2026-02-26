<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class ContentRecommendationsOutput
{
    /**
     * @param  array<array{topic: string, similarity_score: float, reasoning: string, suggested_format: string, reference_content_ids: array<string>}>  $recommendations
     */
    public function __construct(
        public array $recommendations,
    ) {}
}
