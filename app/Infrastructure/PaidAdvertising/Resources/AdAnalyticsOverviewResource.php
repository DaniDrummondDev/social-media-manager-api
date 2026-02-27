<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Resources;

use App\Application\PaidAdvertising\DTOs\AdAnalyticsOverviewOutput;

final readonly class AdAnalyticsOverviewResource
{
    private function __construct(
        private AdAnalyticsOverviewOutput $output,
    ) {}

    public static function fromOutput(AdAnalyticsOverviewOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => null,
            'type' => 'ad_analytics_overview',
            'attributes' => [
                'total_spend_cents' => $this->output->totalSpendCents,
                'currency' => $this->output->currency,
                'total_impressions' => $this->output->totalImpressions,
                'total_clicks' => $this->output->totalClicks,
                'total_conversions' => $this->output->totalConversions,
                'avg_ctr' => $this->output->avgCtr,
                'avg_cpc' => $this->output->avgCpc,
                'active_boosts' => $this->output->activeBoosts,
                'completed_boosts' => $this->output->completedBoosts,
            ],
        ];
    }
}
