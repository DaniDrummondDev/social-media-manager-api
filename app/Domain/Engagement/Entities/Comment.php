<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Entities;

use App\Domain\Engagement\Events\CommentCaptured;
use App\Domain\Engagement\Events\CommentReplied;
use App\Domain\Engagement\Exceptions\CommentAlreadyRepliedException;
use App\Domain\Engagement\ValueObjects\Sentiment;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use DateTimeImmutable;

final readonly class Comment
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $contentId,
        public Uuid $socialAccountId,
        public SocialProvider $provider,
        public string $externalCommentId,
        public string $authorName,
        public ?string $authorExternalId,
        public ?string $authorProfileUrl,
        public string $text,
        public ?Sentiment $sentiment,
        public ?float $sentimentScore,
        public bool $isRead,
        public bool $isFromOwner,
        public ?DateTimeImmutable $repliedAt,
        public ?Uuid $repliedBy,
        public bool $repliedByAutomation,
        public ?string $replyText,
        public ?string $replyExternalId,
        public DateTimeImmutable $commentedAt,
        public DateTimeImmutable $capturedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $contentId,
        Uuid $socialAccountId,
        SocialProvider $provider,
        string $externalCommentId,
        string $authorName,
        ?string $authorExternalId,
        ?string $authorProfileUrl,
        string $text,
        ?Sentiment $sentiment,
        ?float $sentimentScore,
        bool $isFromOwner,
        DateTimeImmutable $commentedAt,
        string $userId = 'system',
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            contentId: $contentId,
            socialAccountId: $socialAccountId,
            provider: $provider,
            externalCommentId: $externalCommentId,
            authorName: $authorName,
            authorExternalId: $authorExternalId,
            authorProfileUrl: $authorProfileUrl,
            text: $text,
            sentiment: $sentiment,
            sentimentScore: $sentimentScore,
            isRead: false,
            isFromOwner: $isFromOwner,
            repliedAt: null,
            repliedBy: null,
            repliedByAutomation: false,
            replyText: null,
            replyExternalId: null,
            commentedAt: $commentedAt,
            capturedAt: $now,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new CommentCaptured(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    contentId: (string) $contentId,
                    provider: $provider->value,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $contentId,
        Uuid $socialAccountId,
        SocialProvider $provider,
        string $externalCommentId,
        string $authorName,
        ?string $authorExternalId,
        ?string $authorProfileUrl,
        string $text,
        ?Sentiment $sentiment,
        ?float $sentimentScore,
        bool $isRead,
        bool $isFromOwner,
        ?DateTimeImmutable $repliedAt,
        ?Uuid $repliedBy,
        bool $repliedByAutomation,
        ?string $replyText,
        ?string $replyExternalId,
        DateTimeImmutable $commentedAt,
        DateTimeImmutable $capturedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            contentId: $contentId,
            socialAccountId: $socialAccountId,
            provider: $provider,
            externalCommentId: $externalCommentId,
            authorName: $authorName,
            authorExternalId: $authorExternalId,
            authorProfileUrl: $authorProfileUrl,
            text: $text,
            sentiment: $sentiment,
            sentimentScore: $sentimentScore,
            isRead: $isRead,
            isFromOwner: $isFromOwner,
            repliedAt: $repliedAt,
            repliedBy: $repliedBy,
            repliedByAutomation: $repliedByAutomation,
            replyText: $replyText,
            replyExternalId: $replyExternalId,
            commentedAt: $commentedAt,
            capturedAt: $capturedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function markAsRead(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            externalCommentId: $this->externalCommentId,
            authorName: $this->authorName,
            authorExternalId: $this->authorExternalId,
            authorProfileUrl: $this->authorProfileUrl,
            text: $this->text,
            sentiment: $this->sentiment,
            sentimentScore: $this->sentimentScore,
            isRead: true,
            isFromOwner: $this->isFromOwner,
            repliedAt: $this->repliedAt,
            repliedBy: $this->repliedBy,
            repliedByAutomation: $this->repliedByAutomation,
            replyText: $this->replyText,
            replyExternalId: $this->replyExternalId,
            commentedAt: $this->commentedAt,
            capturedAt: $this->capturedAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function reply(string $text, Uuid $userId, ?string $replyExternalId = null): self
    {
        if ($this->isReplied()) {
            throw new CommentAlreadyRepliedException(
                "Comentário '{$this->id}' já foi respondido.",
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            externalCommentId: $this->externalCommentId,
            authorName: $this->authorName,
            authorExternalId: $this->authorExternalId,
            authorProfileUrl: $this->authorProfileUrl,
            text: $this->text,
            sentiment: $this->sentiment,
            sentimentScore: $this->sentimentScore,
            isRead: true,
            isFromOwner: $this->isFromOwner,
            repliedAt: $now,
            repliedBy: $userId,
            repliedByAutomation: false,
            replyText: $text,
            replyExternalId: $replyExternalId,
            commentedAt: $this->commentedAt,
            capturedAt: $this->capturedAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new CommentReplied(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $userId,
                    repliedBy: (string) $userId,
                ),
            ],
        );
    }

    public function replyByAutomation(string $text, Uuid $ruleId, ?string $replyExternalId = null): self
    {
        if ($this->isReplied()) {
            throw new CommentAlreadyRepliedException(
                "Comentário '{$this->id}' já foi respondido.",
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            externalCommentId: $this->externalCommentId,
            authorName: $this->authorName,
            authorExternalId: $this->authorExternalId,
            authorProfileUrl: $this->authorProfileUrl,
            text: $this->text,
            sentiment: $this->sentiment,
            sentimentScore: $this->sentimentScore,
            isRead: true,
            isFromOwner: $this->isFromOwner,
            repliedAt: $now,
            repliedBy: null,
            repliedByAutomation: true,
            replyText: $text,
            replyExternalId: $replyExternalId,
            commentedAt: $this->commentedAt,
            capturedAt: $this->capturedAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new CommentReplied(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: 'automation',
                    repliedBy: 'automation:'.(string) $ruleId,
                ),
            ],
        );
    }

    public function isReplied(): bool
    {
        return $this->repliedAt !== null;
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            provider: $this->provider,
            externalCommentId: $this->externalCommentId,
            authorName: $this->authorName,
            authorExternalId: $this->authorExternalId,
            authorProfileUrl: $this->authorProfileUrl,
            text: $this->text,
            sentiment: $this->sentiment,
            sentimentScore: $this->sentimentScore,
            isRead: $this->isRead,
            isFromOwner: $this->isFromOwner,
            repliedAt: $this->repliedAt,
            repliedBy: $this->repliedBy,
            repliedByAutomation: $this->repliedByAutomation,
            replyText: $this->replyText,
            replyExternalId: $this->replyExternalId,
            commentedAt: $this->commentedAt,
            capturedAt: $this->capturedAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
