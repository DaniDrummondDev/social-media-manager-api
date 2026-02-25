<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Resources;

use App\Application\Publishing\DTOs\CalendarOutput;

final readonly class CalendarResource
{
    /**
     * @param  array<array{date: string, posts: array<mixed>, count: int}>  $days
     */
    private function __construct(
        private string $periodStart,
        private string $periodEnd,
        private array $days,
        private int $totalPosts,
    ) {}

    public static function fromOutput(CalendarOutput $output): self
    {
        $days = array_map(fn ($day) => [
            'date' => $day->date,
            'posts' => $day->posts,
            'count' => $day->count,
        ], $output->days);

        return new self(
            periodStart: $output->periodStart,
            periodEnd: $output->periodEnd,
            days: $days,
            totalPosts: $output->totalPosts,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'period' => [
                'start' => $this->periodStart,
                'end' => $this->periodEnd,
            ],
            'days' => $this->days,
            'total_posts' => $this->totalPosts,
        ];
    }
}
