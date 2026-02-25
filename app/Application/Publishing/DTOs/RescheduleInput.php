<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class RescheduleInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $scheduledPostId,
        public string $scheduledAt,
    ) {}
}
