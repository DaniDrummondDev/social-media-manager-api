<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\PredictionCalculated;
use App\Domain\AIIntelligence\ValueObjects\PredictionBreakdown;
use App\Domain\AIIntelligence\ValueObjects\PredictionRecommendation;
use App\Domain\AIIntelligence\ValueObjects\PredictionScore;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class PerformancePrediction
{
    /**
     * @param  array<string>|null  $similarContentIds
     * @param  array<PredictionRecommendation>  $recommendations
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $contentId,
        public string $provider,
        public PredictionScore $overallScore,
        public PredictionBreakdown $breakdown,
        public ?array $similarContentIds,
        public array $recommendations,
        public string $modelVersion,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string>|null  $similarContentIds
     * @param  array<PredictionRecommendation>  $recommendations
     */
    public static function create(
        Uuid $organizationId,
        Uuid $contentId,
        string $provider,
        PredictionScore $overallScore,
        PredictionBreakdown $breakdown,
        ?array $similarContentIds,
        array $recommendations,
        string $modelVersion,
        string $userId,
    ): self {
        $id = Uuid::generate();

        return new self(
            id: $id,
            organizationId: $organizationId,
            contentId: $contentId,
            provider: $provider,
            overallScore: $overallScore,
            breakdown: $breakdown,
            similarContentIds: $similarContentIds,
            recommendations: $recommendations,
            modelVersion: $modelVersion,
            createdAt: new DateTimeImmutable,
            domainEvents: [
                new PredictionCalculated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    contentId: (string) $contentId,
                    provider: $provider,
                    overallScore: $overallScore->value,
                ),
            ],
        );
    }

    /**
     * @param  array<string>|null  $similarContentIds
     * @param  array<PredictionRecommendation>  $recommendations
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $contentId,
        string $provider,
        PredictionScore $overallScore,
        PredictionBreakdown $breakdown,
        ?array $similarContentIds,
        array $recommendations,
        string $modelVersion,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            contentId: $contentId,
            provider: $provider,
            overallScore: $overallScore,
            breakdown: $breakdown,
            similarContentIds: $similarContentIds,
            recommendations: $recommendations,
            modelVersion: $modelVersion,
            createdAt: $createdAt,
        );
    }
}
