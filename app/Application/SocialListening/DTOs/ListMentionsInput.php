<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class ListMentionsInput
{
    public function __construct(
        public string $organizationId,
        public ?string $queryId = null,
        public ?string $platform = null,
        public ?string $sentiment = null,
        public ?bool $isFlagged = null,
        public ?bool $isRead = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?string $search = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
