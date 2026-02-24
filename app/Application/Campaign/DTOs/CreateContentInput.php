<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class CreateContentInput
{
    /**
     * @param  string[]  $hashtags
     * @param  string[]  $mediaIds
     * @param  array<int, array{provider: string, title?: string|null, body?: string|null, hashtags?: string[]|null, metadata?: array<string, mixed>|null}>  $networkOverrides
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $campaignId,
        public ?string $title = null,
        public ?string $body = null,
        public array $hashtags = [],
        public array $mediaIds = [],
        public array $networkOverrides = [],
    ) {}
}
