<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class ListCommentsInput
{
    public function __construct(
        public string $organizationId,
        public ?string $provider = null,
        public ?string $campaignId = null,
        public ?string $contentId = null,
        public ?string $sentiment = null,
        public ?bool $isRead = null,
        public ?bool $isReplied = null,
        public ?string $search = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
