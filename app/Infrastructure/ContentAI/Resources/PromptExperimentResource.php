<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Resources;

use App\Application\ContentAI\DTOs\PromptExperimentOutput;

final readonly class PromptExperimentResource
{
    public function __construct(
        private PromptExperimentOutput $output,
    ) {}

    public static function fromOutput(PromptExperimentOutput $output): self
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
            'type' => 'prompt_experiment',
            'attributes' => [
                'generation_type' => $this->output->generationType,
                'name' => $this->output->name,
                'status' => $this->output->status,
                'variant_a_id' => $this->output->variantAId,
                'variant_b_id' => $this->output->variantBId,
                'traffic_split' => $this->output->trafficSplit,
                'min_sample_size' => $this->output->minSampleSize,
                'variant_a_uses' => $this->output->variantAUses,
                'variant_b_uses' => $this->output->variantBUses,
                'acceptance_rate_a' => $this->output->acceptanceRateA,
                'acceptance_rate_b' => $this->output->acceptanceRateB,
                'winner_id' => $this->output->winnerId,
                'confidence_level' => $this->output->confidenceLevel,
                'started_at' => $this->output->startedAt,
                'completed_at' => $this->output->completedAt,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
