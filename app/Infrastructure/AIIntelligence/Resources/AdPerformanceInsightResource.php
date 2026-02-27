<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\AdPerformanceInsightOutput;

final readonly class AdPerformanceInsightResource
{
    private function __construct(
        private AdPerformanceInsightOutput $output,
    ) {}

    public static function fromOutput(AdPerformanceInsightOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'ad_performance_insight',
            'attributes' => [
                'ad_insight_type' => $this->output->adInsightType,
                'ad_insight_label' => $this->output->adInsightLabel,
                'insight_data' => $this->output->insightData,
                'sample_size' => $this->output->sampleSize,
                'confidence_level' => $this->output->confidenceLevel,
                'period_start' => $this->output->periodStart,
                'period_end' => $this->output->periodEnd,
                'generated_at' => $this->output->generatedAt,
                'expires_at' => $this->output->expiresAt,
            ],
        ];
    }
}
