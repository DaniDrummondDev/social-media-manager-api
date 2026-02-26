<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\ContentGapAnalysis;

final readonly class GapAnalysisOutput
{
    /**
     * @param  array<string>  $competitorQueryIds
     * @param  array<array{topic: string, frequency: int, avg_engagement: float}>  $ourTopics
     * @param  array<array{topic: string, source_competitor: string, frequency: int, avg_engagement: float}>  $competitorTopics
     * @param  array<array{topic: string, opportunity_score: int, competitor_count: int, recommendation: string}>  $gaps
     * @param  array<array{topic: string, reason: string, suggested_content_type: string, estimated_impact: string}>  $opportunities
     */
    public function __construct(
        public string $id,
        public string $status,
        public array $competitorQueryIds,
        public string $analysisPeriodStart,
        public string $analysisPeriodEnd,
        public array $ourTopics,
        public array $competitorTopics,
        public array $gaps,
        public array $opportunities,
        public string $generatedAt,
        public string $expiresAt,
    ) {}

    public static function fromEntity(ContentGapAnalysis $analysis): self
    {
        return new self(
            id: (string) $analysis->id,
            status: $analysis->status->value,
            competitorQueryIds: $analysis->competitorQueryIds,
            analysisPeriodStart: $analysis->analysisPeriodStart->format('Y-m-d'),
            analysisPeriodEnd: $analysis->analysisPeriodEnd->format('Y-m-d'),
            ourTopics: $analysis->ourTopics,
            competitorTopics: $analysis->competitorTopics,
            gaps: $analysis->gaps,
            opportunities: $analysis->opportunities,
            generatedAt: $analysis->generatedAt->format('c'),
            expiresAt: $analysis->expiresAt->format('c'),
        );
    }
}
