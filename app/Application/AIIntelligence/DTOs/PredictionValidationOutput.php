<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\PredictionValidation;

final readonly class PredictionValidationOutput
{
    /**
     * @param  array<string, mixed>  $metricsSnapshot
     */
    public function __construct(
        public string $id,
        public string $predictionId,
        public string $contentId,
        public string $provider,
        public int $predictedScore,
        public ?float $actualEngagementRate,
        public ?int $actualNormalizedScore,
        public ?int $absoluteError,
        public ?float $accuracyPercentage,
        public ?string $grade,
        public array $metricsSnapshot,
        public string $validatedAt,
        public string $metricsCapturedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(PredictionValidation $validation): self
    {
        return new self(
            id: (string) $validation->id,
            predictionId: (string) $validation->predictionId,
            contentId: (string) $validation->contentId,
            provider: $validation->provider,
            predictedScore: $validation->predictedScore,
            actualEngagementRate: $validation->actualEngagementRate,
            actualNormalizedScore: $validation->actualNormalizedScore,
            absoluteError: $validation->accuracy?->absoluteError,
            accuracyPercentage: $validation->accuracy?->accuracyPercentage,
            grade: $validation->getGrade(),
            metricsSnapshot: $validation->metricsSnapshot,
            validatedAt: $validation->validatedAt->format('c'),
            metricsCapturedAt: $validation->metricsCapturedAt->format('c'),
            createdAt: $validation->createdAt->format('c'),
        );
    }
}
