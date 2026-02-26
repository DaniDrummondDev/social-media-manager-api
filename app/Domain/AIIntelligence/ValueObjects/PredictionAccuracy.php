<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

final readonly class PredictionAccuracy
{
    /**
     * @param  int  $absoluteError  |predicted - actual| (0-100)
     * @param  float  $accuracyPercentage  100 - absoluteError (0-100)
     */
    private function __construct(
        public int $absoluteError,
        public float $accuracyPercentage,
    ) {}

    /**
     * Calculate accuracy from predicted and actual scores.
     * accuracy = 100 - |predicted_score - actual_normalized_score|
     */
    public static function calculate(int $predictedScore, int $actualNormalizedScore): self
    {
        $absoluteError = abs($predictedScore - $actualNormalizedScore);
        $accuracy = 100.0 - $absoluteError;

        return new self(
            absoluteError: $absoluteError,
            accuracyPercentage: round($accuracy, 2),
        );
    }

    public static function fromValues(int $absoluteError, float $accuracyPercentage): self
    {
        return new self($absoluteError, $accuracyPercentage);
    }

    public function grade(): string
    {
        return match (true) {
            $this->accuracyPercentage >= 90 => 'A',
            $this->accuracyPercentage >= 75 => 'B',
            $this->accuracyPercentage >= 60 => 'C',
            $this->accuracyPercentage >= 40 => 'D',
            default => 'F',
        };
    }

    public function isGoodPrediction(): bool
    {
        return $this->accuracyPercentage >= 75;
    }
}
