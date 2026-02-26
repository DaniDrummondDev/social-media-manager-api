<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\AudienceInsight;

final readonly class AudienceInsightOutput
{
    /**
     * @param  array<string, mixed>  $insightData
     */
    public function __construct(
        public string $id,
        public string $insightType,
        public array $insightData,
        public int $sourceCommentCount,
        public ?float $confidenceScore,
        public string $periodStart,
        public string $periodEnd,
        public string $generatedAt,
        public string $expiresAt,
    ) {}

    public static function fromEntity(AudienceInsight $insight): self
    {
        return new self(
            id: (string) $insight->id,
            insightType: $insight->insightType->value,
            insightData: $insight->insightData,
            sourceCommentCount: $insight->sourceCommentCount,
            confidenceScore: $insight->confidenceScore,
            periodStart: $insight->periodStart->format('c'),
            periodEnd: $insight->periodEnd->format('c'),
            generatedAt: $insight->generatedAt->format('c'),
            expiresAt: $insight->expiresAt->format('c'),
        );
    }
}
