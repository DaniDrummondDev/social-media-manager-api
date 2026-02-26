<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Events\ContentProfileGenerated;
use App\Domain\AIIntelligence\Exceptions\ContentProfileExpiredException;
use App\Domain\AIIntelligence\Exceptions\InvalidSuggestionStatusTransitionException;
use App\Domain\AIIntelligence\ValueObjects\ContentFingerprint;
use App\Domain\AIIntelligence\ValueObjects\EngagementPattern;
use App\Domain\AIIntelligence\ValueObjects\ProfileStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class ContentProfile
{
    /**
     * @param  array<array{theme: string, score: float, content_count: int}>  $topThemes
     * @param  array<string, mixed>  $highPerformerTraits
     * @param  array<float>|null  $centroidEmbedding
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public ?Uuid $socialAccountId,
        public ?string $provider,
        public int $totalContentsAnalyzed,
        public array $topThemes,
        public ?EngagementPattern $engagementPatterns,
        public ?ContentFingerprint $contentFingerprint,
        public array $highPerformerTraits,
        public ?array $centroidEmbedding,
        public ProfileStatus $status,
        public DateTimeImmutable $generatedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        ?Uuid $socialAccountId,
        ?string $provider,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            socialAccountId: $socialAccountId,
            provider: $provider,
            totalContentsAnalyzed: 0,
            topThemes: [],
            engagementPatterns: null,
            contentFingerprint: null,
            highPerformerTraits: [],
            centroidEmbedding: null,
            status: ProfileStatus::Generating,
            generatedAt: $now,
            expiresAt: $now->modify('+7 days'),
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new ContentProfileGenerated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    provider: $provider,
                    contentsAnalyzed: 0,
                ),
            ],
        );
    }

    /**
     * @param  array<array{theme: string, score: float, content_count: int}>  $topThemes
     * @param  array<string, mixed>  $highPerformerTraits
     * @param  array<float>|null  $centroidEmbedding
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        ?Uuid $socialAccountId,
        ?string $provider,
        int $totalContentsAnalyzed,
        array $topThemes,
        ?EngagementPattern $engagementPatterns,
        ?ContentFingerprint $contentFingerprint,
        array $highPerformerTraits,
        ?array $centroidEmbedding,
        ProfileStatus $status,
        DateTimeImmutable $generatedAt,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            socialAccountId: $socialAccountId,
            provider: $provider,
            totalContentsAnalyzed: $totalContentsAnalyzed,
            topThemes: $topThemes,
            engagementPatterns: $engagementPatterns,
            contentFingerprint: $contentFingerprint,
            highPerformerTraits: $highPerformerTraits,
            centroidEmbedding: $centroidEmbedding,
            status: $status,
            generatedAt: $generatedAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param  array<array{theme: string, score: float, content_count: int}>  $topThemes
     * @param  array<string, mixed>  $highPerformerTraits
     * @param  array<float>|null  $centroidEmbedding
     */
    public function complete(
        int $totalContentsAnalyzed,
        array $topThemes,
        EngagementPattern $engagementPatterns,
        ContentFingerprint $contentFingerprint,
        array $highPerformerTraits,
        ?array $centroidEmbedding,
    ): self {
        $this->assertCanTransitionTo(ProfileStatus::Generated);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            totalContentsAnalyzed: $totalContentsAnalyzed,
            topThemes: $topThemes,
            engagementPatterns: $engagementPatterns,
            contentFingerprint: $contentFingerprint,
            highPerformerTraits: $highPerformerTraits,
            centroidEmbedding: $centroidEmbedding,
            status: ProfileStatus::Generated,
            generatedAt: $this->generatedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable;
    }

    public function markExpired(): self
    {
        if ($this->status === ProfileStatus::Expired) {
            throw new ContentProfileExpiredException;
        }

        $this->assertCanTransitionTo(ProfileStatus::Expired);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            totalContentsAnalyzed: $this->totalContentsAnalyzed,
            topThemes: $this->topThemes,
            engagementPatterns: $this->engagementPatterns,
            contentFingerprint: $this->contentFingerprint,
            highPerformerTraits: $this->highPerformerTraits,
            centroidEmbedding: $this->centroidEmbedding,
            status: ProfileStatus::Expired,
            generatedAt: $this->generatedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    private function assertCanTransitionTo(ProfileStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidSuggestionStatusTransitionException(
                $this->status->value,
                $target->value,
            );
        }
    }
}
