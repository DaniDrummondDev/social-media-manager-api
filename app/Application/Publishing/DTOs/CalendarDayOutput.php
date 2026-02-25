<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class CalendarDayOutput
{
    /**
     * @param  array<array{id: string, scheduled_at: string, provider: string, status: string, content_title: ?string}>  $posts
     */
    public function __construct(
        public string $date,
        public array $posts,
        public int $count,
    ) {}
}
