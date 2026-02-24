<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class CampaignStatsOutput
{
    public function __construct(
        public int $totalContents,
        public int $draft,
        public int $ready,
        public int $scheduled,
        public int $published,
    ) {}

    /**
     * @param  array<string, int>  $counts
     */
    public static function fromCounts(array $counts): self
    {
        $draft = $counts['draft'] ?? 0;
        $ready = $counts['ready'] ?? 0;
        $scheduled = $counts['scheduled'] ?? 0;
        $published = $counts['published'] ?? 0;

        return new self(
            totalContents: $draft + $ready + $scheduled + $published,
            draft: $draft,
            ready: $ready,
            scheduled: $scheduled,
            published: $published,
        );
    }

    /**
     * @return array{total_contents: int, draft: int, ready: int, scheduled: int, published: int}
     */
    public function toArray(): array
    {
        return [
            'total_contents' => $this->totalContents,
            'draft' => $this->draft,
            'ready' => $this->ready,
            'scheduled' => $this->scheduled,
            'published' => $this->published,
        ];
    }
}
