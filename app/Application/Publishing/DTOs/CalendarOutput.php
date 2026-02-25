<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class CalendarOutput
{
    /**
     * @param  CalendarDayOutput[]  $days
     */
    public function __construct(
        public string $periodStart,
        public string $periodEnd,
        public array $days,
        public int $totalPosts,
    ) {}
}
