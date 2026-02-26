<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\PredictionOutput;

final readonly class PredictionResource
{
    private function __construct(private PredictionOutput $output) {}

    public static function fromOutput(PredictionOutput $output): self
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
            'type' => 'performance_prediction',
            'attributes' => [
                'content_id' => $this->output->contentId,
                'provider' => $this->output->provider,
                'overall_score' => $this->output->overallScore,
                'breakdown' => $this->output->breakdown,
                'similar_content_ids' => $this->output->similarContentIds,
                'recommendations' => $this->output->recommendations,
                'model_version' => $this->output->modelVersion,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
