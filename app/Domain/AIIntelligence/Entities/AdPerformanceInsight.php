<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\AdPerformanceAggregated;
use App\Domain\AIIntelligence\Exceptions\AdPerformanceInsightExpiredException;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AdPerformanceInsight
{
    private const int TTL_DAYS = 7;
    private const int MIN_BOOSTS_FOR_INSIGHT = 5;

    /**
     * @param  array<string, mixed>  $insightData
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public AdInsightType $adInsightType,
        public array $insightData,
        public int $sampleSize,
        public ConfidenceLevel $confidenceLevel,
        public DateTimeImmutable $periodStart,
        public DateTimeImmutable $periodEnd,
        public DateTimeImmutable $generatedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $insightData
     */
    public static function create(
        Uuid $organizationId,
        AdInsightType $adInsightType,
        array $insightData,
        int $sampleSize,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;
        $confidenceLevel = ConfidenceLevel::fromSampleSize($sampleSize);

        return new self(
            id: $id,
            organizationId: $organizationId,
            adInsightType: $adInsightType,
            insightData: $insightData,
            sampleSize: $sampleSize,
            confidenceLevel: $confidenceLevel,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            generatedAt: $now,
            expiresAt: $now->modify('+'.self::TTL_DAYS.' days'),
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new AdPerformanceAggregated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    adInsightType: $adInsightType->value,
                    sampleSize: $sampleSize,
                    confidenceLevel: $confidenceLevel->value,
                    isRefresh: false,
                ),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $insightData
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        AdInsightType $adInsightType,
        array $insightData,
        int $sampleSize,
        ConfidenceLevel $confidenceLevel,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        DateTimeImmutable $generatedAt,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            adInsightType: $adInsightType,
            insightData: $insightData,
            sampleSize: $sampleSize,
            confidenceLevel: $confidenceLevel,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            generatedAt: $generatedAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param  array<string, mixed>  $insightData
     */
    public function refresh(
        array $insightData,
        int $sampleSize,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        string $userId,
    ): self {
        $now = new DateTimeImmutable;
        $confidenceLevel = ConfidenceLevel::fromSampleSize($sampleSize);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            adInsightType: $this->adInsightType,
            insightData: $insightData,
            sampleSize: $sampleSize,
            confidenceLevel: $confidenceLevel,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            generatedAt: $now,
            expiresAt: $now->modify('+'.self::TTL_DAYS.' days'),
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new AdPerformanceAggregated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    adInsightType: $this->adInsightType->value,
                    sampleSize: $sampleSize,
                    confidenceLevel: $confidenceLevel->value,
                    isRefresh: true,
                ),
            ],
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable;
    }

    public function assertNotExpired(): void
    {
        if ($this->isExpired()) {
            throw new AdPerformanceInsightExpiredException;
        }
    }

    public static function hasEnoughData(int $boostCount): bool
    {
        return $boostCount >= self::MIN_BOOSTS_FOR_INSIGHT;
    }

    public static function minBoostsRequired(): int
    {
        return self::MIN_BOOSTS_FOR_INSIGHT;
    }
}
