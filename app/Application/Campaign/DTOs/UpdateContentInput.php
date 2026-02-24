<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class UpdateContentInput
{
    /**
     * @param  string[]|null  $hashtags
     * @param  string[]|null  $mediaIds
     * @param  array<int, array{provider: string, title?: string|null, body?: string|null, hashtags?: string[]|null, metadata?: array<string, mixed>|null}>|null  $networkOverrides
     */
    public function __construct(
        public string $organizationId,
        public string $contentId,
        public ?string $title = null,
        public ?string $body = null,
        public ?array $hashtags = null,
        public ?array $mediaIds = null,
        public ?array $networkOverrides = null,
    ) {}
}
