<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\Entities\Comment;

final readonly class CommentOutput
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $contentId,
        public string $socialAccountId,
        public string $provider,
        public string $externalCommentId,
        public string $authorName,
        public ?string $authorExternalId,
        public ?string $authorProfileUrl,
        public string $text,
        public ?string $sentiment,
        public ?float $sentimentScore,
        public bool $isRead,
        public bool $isFromOwner,
        public ?string $repliedAt,
        public ?string $repliedBy,
        public bool $repliedByAutomation,
        public ?string $replyText,
        public ?string $replyExternalId,
        public string $commentedAt,
        public string $capturedAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Comment $comment): self
    {
        return new self(
            id: (string) $comment->id,
            organizationId: (string) $comment->organizationId,
            contentId: (string) $comment->contentId,
            socialAccountId: (string) $comment->socialAccountId,
            provider: $comment->provider->value,
            externalCommentId: $comment->externalCommentId,
            authorName: $comment->authorName,
            authorExternalId: $comment->authorExternalId,
            authorProfileUrl: $comment->authorProfileUrl,
            text: $comment->text,
            sentiment: $comment->sentiment?->value,
            sentimentScore: $comment->sentimentScore,
            isRead: $comment->isRead,
            isFromOwner: $comment->isFromOwner,
            repliedAt: $comment->repliedAt?->format('c'),
            repliedBy: $comment->repliedBy !== null ? (string) $comment->repliedBy : null,
            repliedByAutomation: $comment->repliedByAutomation,
            replyText: $comment->replyText,
            replyExternalId: $comment->replyExternalId,
            commentedAt: $comment->commentedAt->format('c'),
            capturedAt: $comment->capturedAt->format('c'),
            createdAt: $comment->createdAt->format('c'),
            updatedAt: $comment->updatedAt->format('c'),
        );
    }
}
