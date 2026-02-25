<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

final readonly class GetContentAnalyticsOutput
{
    /**
     * @param  array<int, array<string, mixed>>  $networks
     */
    public function __construct(
        public string $contentId,
        public ?string $title,
        public ?string $campaignName,
        public ?string $publishedAt,
        public array $networks,
        public ?string $lastSyncedAt,
    ) {}
}
