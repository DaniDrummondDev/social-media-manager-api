<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

final readonly class GetNetworkAnalyticsOutput
{
    /**
     * @param  array<string, mixed>  $account
     * @param  array<string, mixed>  $contentMetrics
     * @param  array<string, mixed>  $comparison
     * @param  array<int, array<string, mixed>>  $top5Contents
     * @param  array<int, array<string, mixed>>  $bestPostingTimes
     * @param  array<int, array<string, mixed>>  $followersTrend
     */
    public function __construct(
        public string $provider,
        public string $period,
        public array $account,
        public array $contentMetrics,
        public array $comparison,
        public array $top5Contents,
        public array $bestPostingTimes,
        public array $followersTrend,
    ) {}
}
