<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\BestTimesHeatmapOutput;

final readonly class BestTimesHeatmapResource
{
    public function __construct(
        private BestTimesHeatmapOutput $output,
    ) {}

    public static function fromOutput(BestTimesHeatmapOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'heatmap' => $this->output->heatmap,
            'provider' => $this->output->provider,
            'confidence_level' => $this->output->confidenceLevel,
            'sample_size' => $this->output->sampleSize,
            'calculated_at' => $this->output->calculatedAt,
        ];
    }
}
