<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\AudienceInsightsRefreshed;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AudienceInsight
{
    /**
     * @param  array<string, mixed>  $insightData
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public ?Uuid $socialAccountId,
        public InsightType $insightType,
        public array $insightData,
        public int $sourceCommentCount,
        public DateTimeImmutable $periodStart,
        public DateTimeImmutable $periodEnd,
        public ?float $confidenceScore,
        public DateTimeImmutable $generatedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $insightData
     */
    public static function create(
        Uuid $organizationId,
        ?Uuid $socialAccountId,
        InsightType $insightType,
        array $insightData,
        int $sourceCommentCount,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        ?float $confidenceScore,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            socialAccountId: $socialAccountId,
            insightType: $insightType,
            insightData: $insightData,
            sourceCommentCount: $sourceCommentCount,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            confidenceScore: $confidenceScore,
            generatedAt: $now,
            expiresAt: $now->modify('+7 days'),
            createdAt: $now,
            domainEvents: [
                new AudienceInsightsRefreshed(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    insightType: $insightType->value,
                    sourceCommentCount: $sourceCommentCount,
                    confidenceScore: $confidenceScore,
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
        ?Uuid $socialAccountId,
        InsightType $insightType,
        array $insightData,
        int $sourceCommentCount,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        ?float $confidenceScore,
        DateTimeImmutable $generatedAt,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            socialAccountId: $socialAccountId,
            insightType: $insightType,
            insightData: $insightData,
            sourceCommentCount: $sourceCommentCount,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            confidenceScore: $confidenceScore,
            generatedAt: $generatedAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable;
    }
}
