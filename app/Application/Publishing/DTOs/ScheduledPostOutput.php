<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

use App\Domain\Publishing\Entities\ScheduledPost;

final readonly class ScheduledPostOutput
{
    /**
     * @param  array{code: string, message: string, is_permanent: bool}|null  $lastError
     */
    public function __construct(
        public string $id,
        public string $contentId,
        public string $socialAccountId,
        public string $provider,
        public string $username,
        public ?string $scheduledAt,
        public string $status,
        public ?string $publishedAt,
        public ?string $externalPostId,
        public ?string $externalPostUrl,
        public int $attempts,
        public int $maxAttempts,
        public ?array $lastError,
        public ?string $nextRetryAt,
        public ?string $contentTitle,
        public ?string $campaignName,
        public string $createdAt,
    ) {}

    public static function fromEntity(
        ScheduledPost $post,
        ?string $provider = null,
        ?string $username = null,
        ?string $contentTitle = null,
        ?string $campaignName = null,
    ): self {
        return new self(
            id: (string) $post->id,
            contentId: (string) $post->contentId,
            socialAccountId: (string) $post->socialAccountId,
            provider: $provider ?? '',
            username: $username ?? '',
            scheduledAt: $post->scheduledAt->toDateTimeImmutable()->format('c'),
            status: $post->status->value,
            publishedAt: $post->publishedAt?->format('c'),
            externalPostId: $post->externalPostId,
            externalPostUrl: $post->externalPostUrl,
            attempts: $post->attempts,
            maxAttempts: $post->maxAttempts,
            lastError: $post->lastError?->toArray(),
            nextRetryAt: $post->nextRetryAt?->format('c'),
            contentTitle: $contentTitle,
            campaignName: $campaignName,
            createdAt: $post->createdAt->format('c'),
        );
    }
}
