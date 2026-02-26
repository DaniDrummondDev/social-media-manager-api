<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Entities;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Events\MentionDetected;
use App\Domain\SocialListening\Events\MentionFlagged;
use App\Domain\SocialListening\ValueObjects\Sentiment;
use DateTimeImmutable;

final readonly class Mention
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $queryId,
        public Uuid $organizationId,
        public string $platform,
        public string $externalId,
        public string $authorUsername,
        public string $authorDisplayName,
        public ?int $authorFollowerCount,
        public ?string $profileUrl,
        public string $content,
        public ?string $url,
        public ?Sentiment $sentiment,
        public ?float $sentimentScore,
        public int $reach,
        public int $engagementCount,
        public bool $isFlagged,
        public bool $isRead,
        public DateTimeImmutable $publishedAt,
        public DateTimeImmutable $detectedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $queryId,
        Uuid $organizationId,
        string $platform,
        string $externalId,
        string $authorUsername,
        string $authorDisplayName,
        ?int $authorFollowerCount,
        ?string $profileUrl,
        string $content,
        ?string $url,
        ?Sentiment $sentiment,
        ?float $sentimentScore,
        int $reach,
        int $engagementCount,
        DateTimeImmutable $publishedAt,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            queryId: $queryId,
            organizationId: $organizationId,
            platform: $platform,
            externalId: $externalId,
            authorUsername: $authorUsername,
            authorDisplayName: $authorDisplayName,
            authorFollowerCount: $authorFollowerCount,
            profileUrl: $profileUrl,
            content: $content,
            url: $url,
            sentiment: $sentiment,
            sentimentScore: $sentimentScore,
            reach: $reach,
            engagementCount: $engagementCount,
            isFlagged: false,
            isRead: false,
            publishedAt: $publishedAt,
            detectedAt: $now,
            domainEvents: [
                new MentionDetected(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: 'system',
                    queryId: (string) $queryId,
                    platform: $platform,
                    sentiment: $sentiment?->value,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $queryId,
        Uuid $organizationId,
        string $platform,
        string $externalId,
        string $authorUsername,
        string $authorDisplayName,
        ?int $authorFollowerCount,
        ?string $profileUrl,
        string $content,
        ?string $url,
        ?Sentiment $sentiment,
        ?float $sentimentScore,
        int $reach,
        int $engagementCount,
        bool $isFlagged,
        bool $isRead,
        DateTimeImmutable $publishedAt,
        DateTimeImmutable $detectedAt,
    ): self {
        return new self(
            id: $id,
            queryId: $queryId,
            organizationId: $organizationId,
            platform: $platform,
            externalId: $externalId,
            authorUsername: $authorUsername,
            authorDisplayName: $authorDisplayName,
            authorFollowerCount: $authorFollowerCount,
            profileUrl: $profileUrl,
            content: $content,
            url: $url,
            sentiment: $sentiment,
            sentimentScore: $sentimentScore,
            reach: $reach,
            engagementCount: $engagementCount,
            isFlagged: $isFlagged,
            isRead: $isRead,
            publishedAt: $publishedAt,
            detectedAt: $detectedAt,
        );
    }

    public function flag(string $userId): self
    {
        return new self(
            id: $this->id,
            queryId: $this->queryId,
            organizationId: $this->organizationId,
            platform: $this->platform,
            externalId: $this->externalId,
            authorUsername: $this->authorUsername,
            authorDisplayName: $this->authorDisplayName,
            authorFollowerCount: $this->authorFollowerCount,
            profileUrl: $this->profileUrl,
            content: $this->content,
            url: $this->url,
            sentiment: $this->sentiment,
            sentimentScore: $this->sentimentScore,
            reach: $this->reach,
            engagementCount: $this->engagementCount,
            isFlagged: true,
            isRead: $this->isRead,
            publishedAt: $this->publishedAt,
            detectedAt: $this->detectedAt,
            domainEvents: [
                new MentionFlagged(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                ),
            ],
        );
    }

    public function unflag(): self
    {
        return new self(
            id: $this->id,
            queryId: $this->queryId,
            organizationId: $this->organizationId,
            platform: $this->platform,
            externalId: $this->externalId,
            authorUsername: $this->authorUsername,
            authorDisplayName: $this->authorDisplayName,
            authorFollowerCount: $this->authorFollowerCount,
            profileUrl: $this->profileUrl,
            content: $this->content,
            url: $this->url,
            sentiment: $this->sentiment,
            sentimentScore: $this->sentimentScore,
            reach: $this->reach,
            engagementCount: $this->engagementCount,
            isFlagged: false,
            isRead: $this->isRead,
            publishedAt: $this->publishedAt,
            detectedAt: $this->detectedAt,
        );
    }

    public function markAsRead(): self
    {
        return new self(
            id: $this->id,
            queryId: $this->queryId,
            organizationId: $this->organizationId,
            platform: $this->platform,
            externalId: $this->externalId,
            authorUsername: $this->authorUsername,
            authorDisplayName: $this->authorDisplayName,
            authorFollowerCount: $this->authorFollowerCount,
            profileUrl: $this->profileUrl,
            content: $this->content,
            url: $this->url,
            sentiment: $this->sentiment,
            sentimentScore: $this->sentimentScore,
            reach: $this->reach,
            engagementCount: $this->engagementCount,
            isFlagged: $this->isFlagged,
            isRead: true,
            publishedAt: $this->publishedAt,
            detectedAt: $this->detectedAt,
        );
    }

    public function assignSentiment(Sentiment $sentiment, float $score): self
    {
        return new self(
            id: $this->id,
            queryId: $this->queryId,
            organizationId: $this->organizationId,
            platform: $this->platform,
            externalId: $this->externalId,
            authorUsername: $this->authorUsername,
            authorDisplayName: $this->authorDisplayName,
            authorFollowerCount: $this->authorFollowerCount,
            profileUrl: $this->profileUrl,
            content: $this->content,
            url: $this->url,
            sentiment: $sentiment,
            sentimentScore: $score,
            reach: $this->reach,
            engagementCount: $this->engagementCount,
            isFlagged: $this->isFlagged,
            isRead: $this->isRead,
            publishedAt: $this->publishedAt,
            detectedAt: $this->detectedAt,
        );
    }
}
