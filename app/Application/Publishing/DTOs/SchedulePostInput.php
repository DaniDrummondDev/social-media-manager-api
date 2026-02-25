<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class SchedulePostInput
{
    /**
     * @param  string[]  $socialAccountIds
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $contentId,
        public array $socialAccountIds,
        public string $scheduledAt,
    ) {}
}
