<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class SchedulePostOutput
{
    /**
     * @param  ScheduledPostOutput[]  $scheduledPosts
     */
    public function __construct(
        public string $contentId,
        public array $scheduledPosts,
    ) {}
}
