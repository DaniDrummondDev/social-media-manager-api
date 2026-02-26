<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\PredictionValidated;
use App\Domain\AIIntelligence\ValueObjects\PredictionAccuracy;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class PredictionValidation
{
    /**
     * @param  array<string, mixed>  $metricsSnapshot
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $predictionId,
        public Uuid $contentId,
        public string $provider,
        public int $predictedScore,
        public ?float $actualEngagementRate,
        public ?int $actualNormalizedScore,
        public ?PredictionAccuracy $accuracy,
        public array $metricsSnapshot,
        public DateTimeImmutable $validatedAt,
        public DateTimeImmutable $metricsCapturedAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metricsSnapshot
     */
    public static function create(
        Uuid $organizationId,
        Uuid $predictionId,
        Uuid $contentId,
        string $provider,
        int $predictedScore,
        float $actualEngagementRate,
        int $actualNormalizedScore,
        array $metricsSnapshot,
        DateTimeImmutable $metricsCapturedAt,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        $accuracy = PredictionAccuracy::calculate($predictedScore, $actualNormalizedScore);

        return new self(
            id: $id,
            organizationId: $organizationId,
            predictionId: $predictionId,
            contentId: $contentId,
            provider: $provider,
            predictedScore: $predictedScore,
            actualEngagementRate: $actualEngagementRate,
            actualNormalizedScore: $actualNormalizedScore,
            accuracy: $accuracy,
            metricsSnapshot: $metricsSnapshot,
            validatedAt: $now,
            metricsCapturedAt: $metricsCapturedAt,
            createdAt: $now,
            domainEvents: [
                new PredictionValidated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    predictionId: (string) $predictionId,
                    predictedScore: $predictedScore,
                    actualNormalizedScore: $actualNormalizedScore,
                    absoluteError: $accuracy->absoluteError,
                ),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $metricsSnapshot
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $predictionId,
        Uuid $contentId,
        string $provider,
        int $predictedScore,
        ?float $actualEngagementRate,
        ?int $actualNormalizedScore,
        ?PredictionAccuracy $accuracy,
        array $metricsSnapshot,
        DateTimeImmutable $validatedAt,
        DateTimeImmutable $metricsCapturedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            predictionId: $predictionId,
            contentId: $contentId,
            provider: $provider,
            predictedScore: $predictedScore,
            actualEngagementRate: $actualEngagementRate,
            actualNormalizedScore: $actualNormalizedScore,
            accuracy: $accuracy,
            metricsSnapshot: $metricsSnapshot,
            validatedAt: $validatedAt,
            metricsCapturedAt: $metricsCapturedAt,
            createdAt: $createdAt,
        );
    }

    public function isAccurate(): bool
    {
        return $this->accuracy !== null && $this->accuracy->isGoodPrediction();
    }

    public function getGrade(): ?string
    {
        return $this->accuracy?->grade();
    }
}
