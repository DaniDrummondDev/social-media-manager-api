<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Resources;

use App\Application\Analytics\DTOs\GetNetworkAnalyticsOutput;

final readonly class NetworkAnalyticsResource
{
    private function __construct(
        private GetNetworkAnalyticsOutput $output,
    ) {}

    public static function fromOutput(GetNetworkAnalyticsOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->output->provider,
            'period' => $this->output->period,
            'account' => $this->output->account,
            'content_metrics' => $this->output->contentMetrics,
            'comparison' => $this->output->comparison,
            'top_contents' => $this->output->top5Contents,
            'best_posting_times' => $this->output->bestPostingTimes,
            'followers_trend' => $this->output->followersTrend,
        ];
    }
}
