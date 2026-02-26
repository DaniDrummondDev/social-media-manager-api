<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

use App\Domain\SocialListening\Entities\Mention;

final readonly class MentionOutput
{
    public function __construct(
        public string $id,
        public string $queryId,
        public string $platform,
        public string $externalId,
        public string $authorUsername,
        public string $authorDisplayName,
        public ?int $authorFollowerCount,
        public ?string $profileUrl,
        public string $content,
        public ?string $url,
        public ?string $sentiment,
        public ?float $sentimentScore,
        public int $reach,
        public int $engagementCount,
        public bool $isFlagged,
        public bool $isRead,
        public string $publishedAt,
        public string $detectedAt,
    ) {}

    public static function fromEntity(Mention $mention): self
    {
        return new self(
            id: (string) $mention->id,
            queryId: (string) $mention->queryId,
            platform: $mention->platform,
            externalId: $mention->externalId,
            authorUsername: $mention->authorUsername,
            authorDisplayName: $mention->authorDisplayName,
            authorFollowerCount: $mention->authorFollowerCount,
            profileUrl: $mention->profileUrl,
            content: $mention->content,
            url: $mention->url,
            sentiment: $mention->sentiment?->value,
            sentimentScore: $mention->sentimentScore,
            reach: $mention->reach,
            engagementCount: $mention->engagementCount,
            isFlagged: $mention->isFlagged,
            isRead: $mention->isRead,
            publishedAt: $mention->publishedAt->format('c'),
            detectedAt: $mention->detectedAt->format('c'),
        );
    }
}
