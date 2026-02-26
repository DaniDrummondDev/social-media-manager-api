<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\PredictionSummaryOutput;

final readonly class PredictionSummaryResource
{
    private function __construct(private PredictionSummaryOutput $output) {}

    public static function fromOutput(PredictionSummaryOutput $output): self
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
            'provider' => $this->output->provider,
            'overall_score' => $this->output->overallScore,
            'created_at' => $this->output->createdAt,
        ];
    }
}
