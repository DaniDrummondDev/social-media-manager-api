<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\AIIntelligence\ValueObjects\PredictionRecommendation;

final readonly class PredictionOutput
{
    /**
     * @param  array<string, int>  $breakdown
     * @param  array<string>|null  $similarContentIds
     * @param  array<array{type: string, message: string, impact_estimate: string}>  $recommendations
     */
    public function __construct(
        public string $id,
        public string $contentId,
        public string $provider,
        public int $overallScore,
        public array $breakdown,
        public ?array $similarContentIds,
        public array $recommendations,
        public string $modelVersion,
        public string $createdAt,
    ) {}

    public static function fromEntity(PerformancePrediction $prediction): self
    {
        return new self(
            id: (string) $prediction->id,
            contentId: (string) $prediction->contentId,
            provider: $prediction->provider,
            overallScore: $prediction->overallScore->value,
            breakdown: $prediction->breakdown->toArray(),
            similarContentIds: $prediction->similarContentIds,
            recommendations: array_map(
                fn (PredictionRecommendation $r) => $r->toArray(),
                $prediction->recommendations,
            ),
            modelVersion: $prediction->modelVersion,
            createdAt: $prediction->createdAt->format('c'),
        );
    }
}
