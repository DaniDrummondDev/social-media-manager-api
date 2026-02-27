<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;

final readonly class AdPerformanceInsightOutput
{
    /**
     * @param  array<string, mixed>  $insightData
     */
    public function __construct(
        public string $id,
        public string $adInsightType,
        public string $adInsightLabel,
        public array $insightData,
        public int $sampleSize,
        public string $confidenceLevel,
        public string $periodStart,
        public string $periodEnd,
        public string $generatedAt,
        public string $expiresAt,
    ) {}

    public static function fromEntity(AdPerformanceInsight $insight): self
    {
        return new self(
            id: (string) $insight->id,
            adInsightType: $insight->adInsightType->value,
            adInsightLabel: $insight->adInsightType->label(),
            insightData: $insight->insightData,
            sampleSize: $insight->sampleSize,
            confidenceLevel: $insight->confidenceLevel->value,
            periodStart: $insight->periodStart->format('c'),
            periodEnd: $insight->periodEnd->format('c'),
            generatedAt: $insight->generatedAt->format('c'),
            expiresAt: $insight->expiresAt->format('c'),
        );
    }
}
