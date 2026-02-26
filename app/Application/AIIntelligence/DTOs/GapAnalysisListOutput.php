<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\ContentGapAnalysis;

final readonly class GapAnalysisListOutput
{
    public function __construct(
        public string $id,
        public string $status,
        public int $competitorQueryCount,
        public string $analysisPeriodStart,
        public string $analysisPeriodEnd,
        public int $gapCount,
        public int $opportunityCount,
        public string $generatedAt,
        public string $expiresAt,
    ) {}

    public static function fromEntity(ContentGapAnalysis $analysis): self
    {
        return new self(
            id: (string) $analysis->id,
            status: $analysis->status->value,
            competitorQueryCount: count($analysis->competitorQueryIds),
            analysisPeriodStart: $analysis->analysisPeriodStart->format('Y-m-d'),
            analysisPeriodEnd: $analysis->analysisPeriodEnd->format('Y-m-d'),
            gapCount: count($analysis->gaps),
            opportunityCount: count($analysis->opportunities),
            generatedAt: $analysis->generatedAt->format('c'),
            expiresAt: $analysis->expiresAt->format('c'),
        );
    }
}
