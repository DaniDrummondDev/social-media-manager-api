<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Entities;

use App\Domain\Analytics\Events\MetricsSynced;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use DateTimeImmutable;

final readonly class ContentMetric
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $contentId,
        public Uuid $socialAccountId,
        public SocialProvider $provider,
        public ?string $externalPostId,
        public int $impressions,
        public int $reach,
        public int $likes,
        public int $comments,
        public int $shares,
        public int $saves,
        public int $clicks,
        public ?int $views,
        public ?int $watchTimeSeconds,
        public float $engagementRate,
        public DateTimeImmutable $syncedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $contentId,
        Uuid $socialAccountId,
        SocialProvider $provider,
        ?string $externalPostId,
        int $impressions,
        int $reach,
        int $likes,
        int $comments,
        int $shares,
        int $saves,
        int $clicks,
        ?int $views,
        ?int $watchTimeSeconds,
        string $organizationId,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;
        $engagementRate = self::calculateEngagementRate($likes, $comments, $shares, $saves, $reach);

        return new self(
            id: $id,
            contentId: $contentId,
            socialAccountId: $socialAccountId,
            provider: $provider,
            externalPostId: $externalPostId,
            impressions: $impressions,
            reach: $reach,
            likes: $likes,
            comments: $comments,
            shares: $shares,
            saves: $saves,
            clicks: $clicks,
            views: $views,
            watchTimeSeconds: $watchTimeSeconds,
            engagementRate: $engagementRate,
            syncedAt: $now,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new MetricsSynced(
                    aggregateId: (string) $id,
                    organizationId: $organizationId,
                    userId: $userId,
                    socialAccountId: (string) $socialAccountId,
                    provider: $provider->value,
                    syncedAt: $now->format('c'),
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $contentId,
        Uuid $socialAccountId,
        SocialProvider $provider,
        ?string $externalPostId,
        int $impressions,
        int $reach,
        int $likes,
        int $comments,
        int $shares,
        int $saves,
        int $clicks,
        ?int $views,
        ?int $watchTimeSeconds,
        float $engagementRate,
        DateTimeImmutable $syncedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            contentId: $contentId,
            socialAccountId: $socialAccountId,
            provider: $provider,
            externalPostId: $externalPostId,
            impressions: $impressions,
            reach: $reach,
            likes: $likes,
            comments: $comments,
            shares: $shares,
            saves: $saves,
            clicks: $clicks,
            views: $views,
            watchTimeSeconds: $watchTimeSeconds,
            engagementRate: $engagementRate,
            syncedAt: $syncedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function updateMetrics(
        int $impressions,
        int $reach,
        int $likes,
        int $comments,
        int $shares,
        int $saves,
        int $clicks,
        ?int $views,
        ?int $watchTimeSeconds,
        string $organizationId,
        string $userId,
    ): self {
        $now = new DateTimeImmutable;
        $engagementRate = self::calculateEngagementRate($likes, $comments, $shares, $saves, $reach);

        return new self(
            id: $this->id,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            externalPostId: $this->externalPostId,
            impressions: $impressions,
            reach: $reach,
            likes: $likes,
            comments: $comments,
            shares: $shares,
            saves: $saves,
            clicks: $clicks,
            views: $views,
            watchTimeSeconds: $watchTimeSeconds,
            engagementRate: $engagementRate,
            syncedAt: $now,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new MetricsSynced(
                    aggregateId: (string) $this->id,
                    organizationId: $organizationId,
                    userId: $userId,
                    socialAccountId: (string) $this->socialAccountId,
                    provider: $this->provider->value,
                    syncedAt: $now->format('c'),
                ),
            ],
        );
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            externalPostId: $this->externalPostId,
            impressions: $this->impressions,
            reach: $this->reach,
            likes: $this->likes,
            comments: $this->comments,
            shares: $this->shares,
            saves: $this->saves,
            clicks: $this->clicks,
            views: $this->views,
            watchTimeSeconds: $this->watchTimeSeconds,
            engagementRate: $this->engagementRate,
            syncedAt: $this->syncedAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    private static function calculateEngagementRate(
        int $likes,
        int $comments,
        int $shares,
        int $saves,
        int $reach,
    ): float {
        if ($reach === 0) {
            return 0.0;
        }

        return round(($likes + $comments + $shares + $saves) / $reach * 100, 4);
    }
}
