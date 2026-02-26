<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class ContentGapAnalysisResult
{
    /**
     * @param  array<array{topic: string, opportunity_score: int, competitor_count: int, recommendation: string}>  $gaps
     * @param  array<array{topic: string, reason: string, suggested_content_type: string, estimated_impact: string}>  $opportunities
     */
    public function __construct(
        public array $gaps,
        public array $opportunities,
        public ?string $modelUsed,
        public ?int $tokensInput,
        public ?int $tokensOutput,
    ) {}
}
