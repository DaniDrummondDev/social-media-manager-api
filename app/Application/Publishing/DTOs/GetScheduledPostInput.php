<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class GetScheduledPostInput
{
    public function __construct(
        public string $organizationId,
        public string $scheduledPostId,
    ) {}
}
