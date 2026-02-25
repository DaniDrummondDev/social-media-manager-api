<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class ListScheduledPostsInput
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $provider = null,
        public ?string $campaignId = null,
        public ?string $from = null,
        public ?string $to = null,
    ) {}
}
