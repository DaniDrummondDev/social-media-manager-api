<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\PerformancePrediction;

final readonly class PredictionSummaryOutput
{
    public function __construct(
        public string $id,
        public string $provider,
        public int $overallScore,
        public string $createdAt,
    ) {}

    public static function fromEntity(PerformancePrediction $prediction): self
    {
        return new self(
            id: (string) $prediction->id,
            provider: $prediction->provider,
            overallScore: $prediction->overallScore->value,
            createdAt: $prediction->createdAt->format('c'),
        );
    }
}
