<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Resources;

use App\Application\SocialListening\DTOs\ListeningDashboardOutput;
use App\Application\SocialListening\DTOs\PlatformBreakdownOutput;
use App\Application\SocialListening\DTOs\SentimentTrendOutput;

final readonly class ListeningDashboardResource
{
    public function __construct(
        private ListeningDashboardOutput $output,
    ) {}

    public static function fromOutput(ListeningDashboardOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_mentions' => $this->output->totalMentions,
            'sentiment_breakdown' => $this->output->sentimentBreakdown,
            'mentions_trend' => array_map(fn (SentimentTrendOutput $item) => [
                'date' => $item->date,
                'positive' => $item->positive,
                'neutral' => $item->neutral,
                'negative' => $item->negative,
                'total' => $item->total,
            ], $this->output->mentionsTrend),
            'top_authors' => $this->output->topAuthors,
            'top_keywords' => $this->output->topKeywords,
            'platform_breakdown' => array_map(fn (PlatformBreakdownOutput $item) => [
                'platform' => $item->platform,
                'count' => $item->count,
                'percentage' => $item->percentage,
            ], $this->output->platformBreakdown),
            'period' => $this->output->period,
        ];
    }
}
