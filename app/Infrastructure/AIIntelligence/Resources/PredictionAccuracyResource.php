<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\PredictionAccuracyOutput;

final readonly class PredictionAccuracyResource
{
    public function __construct(
        private PredictionAccuracyOutput $output,
    ) {}

    public static function fromOutput(PredictionAccuracyOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => 'prediction_accuracy',
            'type' => 'prediction_accuracy',
            'attributes' => [
                'mean_absolute_error' => $this->output->meanAbsoluteError,
                'total_validations' => $this->output->totalValidations,
                'message' => $this->output->message,
            ],
        ];
    }
}
