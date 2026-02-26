<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\OrgStyleProfileGenerated;
use App\Domain\AIIntelligence\Exceptions\OrgStyleProfileExpiredException;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\AIIntelligence\ValueObjects\StylePreferences;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class OrgStyleProfile
{
    private const int MIN_EDITS_FOR_GENERATION = 10;
    private const int TTL_DAYS = 14;

    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $generationType,
        public int $sampleSize,
        public StylePreferences $stylePreferences,
        public ?string $styleSummary,
        public ConfidenceLevel $confidenceLevel,
        public DateTimeImmutable $generatedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        string $generationType,
        int $sampleSize,
        StylePreferences $stylePreferences,
        ?string $styleSummary,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;
        $confidenceLevel = self::determineConfidence($sampleSize);

        return new self(
            id: $id,
            organizationId: $organizationId,
            generationType: $generationType,
            sampleSize: $sampleSize,
            stylePreferences: $stylePreferences,
            styleSummary: $styleSummary,
            confidenceLevel: $confidenceLevel,
            generatedAt: $now,
            expiresAt: $now->modify('+' . self::TTL_DAYS . ' days'),
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new OrgStyleProfileGenerated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    generationType: $generationType,
                    sampleSize: $sampleSize,
                    confidenceLevel: $confidenceLevel->value,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $generationType,
        int $sampleSize,
        StylePreferences $stylePreferences,
        ?string $styleSummary,
        ConfidenceLevel $confidenceLevel,
        DateTimeImmutable $generatedAt,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            generationType: $generationType,
            sampleSize: $sampleSize,
            stylePreferences: $stylePreferences,
            styleSummary: $styleSummary,
            confidenceLevel: $confidenceLevel,
            generatedAt: $generatedAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function refresh(
        int $sampleSize,
        StylePreferences $stylePreferences,
        ?string $styleSummary,
        string $userId,
    ): self {
        $now = new DateTimeImmutable;
        $confidenceLevel = self::determineConfidence($sampleSize);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            generationType: $this->generationType,
            sampleSize: $sampleSize,
            stylePreferences: $stylePreferences,
            styleSummary: $styleSummary,
            confidenceLevel: $confidenceLevel,
            generatedAt: $now,
            expiresAt: $now->modify('+' . self::TTL_DAYS . ' days'),
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new OrgStyleProfileGenerated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    generationType: $this->generationType,
                    sampleSize: $sampleSize,
                    confidenceLevel: $confidenceLevel->value,
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
            throw new OrgStyleProfileExpiredException;
        }
    }

    public static function hasEnoughData(int $editCount): bool
    {
        return $editCount >= self::MIN_EDITS_FOR_GENERATION;
    }

    public static function minEditsRequired(): int
    {
        return self::MIN_EDITS_FOR_GENERATION;
    }

    /**
     * Confidence levels for style profile based on ADR-017:
     * low (<10 edits), medium (10-50), high (>50)
     */
    private static function determineConfidence(int $sampleSize): ConfidenceLevel
    {
        return match (true) {
            $sampleSize > 50 => ConfidenceLevel::High,
            $sampleSize >= 10 => ConfidenceLevel::Medium,
            default => ConfidenceLevel::Low,
        };
    }
}
