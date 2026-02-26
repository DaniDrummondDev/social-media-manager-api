<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\PredictionValidationOutput;

final readonly class PredictionValidationResource
{
    public function __construct(
        private PredictionValidationOutput $output,
    ) {}

    public static function fromOutput(PredictionValidationOutput $output): self
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
            'type' => 'prediction_validation',
            'attributes' => [
                'prediction_id' => $this->output->predictionId,
                'content_id' => $this->output->contentId,
                'provider' => $this->output->provider,
                'predicted_score' => $this->output->predictedScore,
                'actual_engagement_rate' => $this->output->actualEngagementRate,
                'actual_normalized_score' => $this->output->actualNormalizedScore,
                'absolute_error' => $this->output->absoluteError,
                'accuracy_percentage' => $this->output->accuracyPercentage,
                'grade' => $this->output->grade,
                'validated_at' => $this->output->validatedAt,
            ],
        ];
    }
}
