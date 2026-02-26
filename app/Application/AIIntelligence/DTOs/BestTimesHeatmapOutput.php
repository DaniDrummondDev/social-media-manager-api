<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\PostingTimeRecommendation;

final readonly class BestTimesHeatmapOutput
{
    /**
     * @param  array<array<string, mixed>>  $heatmap
     */
    public function __construct(
        public array $heatmap,
        public ?string $provider,
        public string $confidenceLevel,
        public int $sampleSize,
        public string $calculatedAt,
    ) {}

    public static function fromEntity(PostingTimeRecommendation $recommendation): self
    {
        return new self(
            heatmap: array_map(fn ($slot) => $slot->toArray(), $recommendation->heatmap),
            provider: $recommendation->provider,
            confidenceLevel: $recommendation->confidenceLevel->value,
            sampleSize: $recommendation->sampleSize,
            calculatedAt: $recommendation->calculatedAt->format('c'),
        );
    }
}
