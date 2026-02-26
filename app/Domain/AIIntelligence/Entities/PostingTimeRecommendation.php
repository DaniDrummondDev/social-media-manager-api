<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\PostingTimesUpdated;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\AIIntelligence\ValueObjects\TimeSlotScore;
use App\Domain\AIIntelligence\ValueObjects\TopSlot;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class PostingTimeRecommendation
{
    /**
     * @param  array<TimeSlotScore>  $heatmap
     * @param  array<TopSlot>  $topSlots
     * @param  array<TopSlot>  $worstSlots
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public ?Uuid $socialAccountId,
        public ?string $provider,
        public array $heatmap,
        public array $topSlots,
        public array $worstSlots,
        public int $sampleSize,
        public ConfidenceLevel $confidenceLevel,
        public DateTimeImmutable $calculatedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<TimeSlotScore>  $heatmap
     * @param  array<TopSlot>  $topSlots
     * @param  array<TopSlot>  $worstSlots
     */
    public static function create(
        Uuid $organizationId,
        ?Uuid $socialAccountId,
        ?string $provider,
        array $heatmap,
        array $topSlots,
        array $worstSlots,
        int $sampleSize,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;
        $confidenceLevel = ConfidenceLevel::fromSampleSize($sampleSize);

        return new self(
            id: $id,
            organizationId: $organizationId,
            socialAccountId: $socialAccountId,
            provider: $provider,
            heatmap: $heatmap,
            topSlots: $topSlots,
            worstSlots: $worstSlots,
            sampleSize: $sampleSize,
            confidenceLevel: $confidenceLevel,
            calculatedAt: $now,
            expiresAt: $now->modify('+7 days'),
            createdAt: $now,
            domainEvents: [
                new PostingTimesUpdated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    provider: $provider,
                    confidenceLevel: $confidenceLevel->value,
                ),
            ],
        );
    }

    /**
     * @param  array<TimeSlotScore>  $heatmap
     * @param  array<TopSlot>  $topSlots
     * @param  array<TopSlot>  $worstSlots
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        ?Uuid $socialAccountId,
        ?string $provider,
        array $heatmap,
        array $topSlots,
        array $worstSlots,
        int $sampleSize,
        ConfidenceLevel $confidenceLevel,
        DateTimeImmutable $calculatedAt,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            socialAccountId: $socialAccountId,
            provider: $provider,
            heatmap: $heatmap,
            topSlots: $topSlots,
            worstSlots: $worstSlots,
            sampleSize: $sampleSize,
            confidenceLevel: $confidenceLevel,
            calculatedAt: $calculatedAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable;
    }

    /**
     * @param  array<TimeSlotScore>  $heatmap
     * @param  array<TopSlot>  $topSlots
     * @param  array<TopSlot>  $worstSlots
     */
    public function recalculate(
        array $heatmap,
        array $topSlots,
        array $worstSlots,
        int $sampleSize,
        string $userId,
    ): self {
        $now = new DateTimeImmutable;
        $confidenceLevel = ConfidenceLevel::fromSampleSize($sampleSize);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            heatmap: $heatmap,
            topSlots: $topSlots,
            worstSlots: $worstSlots,
            sampleSize: $sampleSize,
            confidenceLevel: $confidenceLevel,
            calculatedAt: $now,
            expiresAt: $now->modify('+7 days'),
            createdAt: $this->createdAt,
            domainEvents: [
                new PostingTimesUpdated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    provider: $this->provider,
                    confidenceLevel: $confidenceLevel->value,
                ),
            ],
        );
    }
}
