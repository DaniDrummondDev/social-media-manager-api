<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class ScheduledPostListOutput
{
    /**
     * @param  ScheduledPostOutput[]  $items
     */
    public function __construct(
        public array $items,
    ) {}
}
