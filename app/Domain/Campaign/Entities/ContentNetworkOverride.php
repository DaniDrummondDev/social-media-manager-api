<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Entities;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use DateTimeImmutable;

final readonly class ContentNetworkOverride
{
    /**
     * @param  string[]|null  $hashtags
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public Uuid $id,
        public Uuid $contentId,
        public SocialProvider $provider,
        public ?string $title,
        public ?string $body,
        public ?array $hashtags,
        public ?array $metadata,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param  string[]|null  $hashtags
     * @param  array<string, mixed>|null  $metadata
     */
    public static function create(
        Uuid $contentId,
        SocialProvider $provider,
        ?string $title = null,
        ?string $body = null,
        ?array $hashtags = null,
        ?array $metadata = null,
    ): self {
        $now = new DateTimeImmutable;

        return new self(
            id: Uuid::generate(),
            contentId: $contentId,
            provider: $provider,
            title: $title,
            body: $body,
            hashtags: $hashtags,
            metadata: $metadata,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param  string[]|null  $hashtags
     * @param  array<string, mixed>|null  $metadata
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $contentId,
        SocialProvider $provider,
        ?string $title,
        ?string $body,
        ?array $hashtags,
        ?array $metadata,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            contentId: $contentId,
            provider: $provider,
            title: $title,
            body: $body,
            hashtags: $hashtags,
            metadata: $metadata,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }
}
