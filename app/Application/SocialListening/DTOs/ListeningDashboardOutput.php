<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class ListeningDashboardOutput
{
    /**
     * @param  array<string, int>  $sentimentBreakdown
     * @param  array<SentimentTrendOutput>  $mentionsTrend
     * @param  array<array<string, mixed>>  $topAuthors
     * @param  array<array<string, mixed>>  $topKeywords
     * @param  array<PlatformBreakdownOutput>  $platformBreakdown
     */
    public function __construct(
        public int $totalMentions,
        public array $sentimentBreakdown,
        public array $mentionsTrend,
        public array $topAuthors,
        public array $topKeywords,
        public array $platformBreakdown,
        public string $period,
    ) {}
}
