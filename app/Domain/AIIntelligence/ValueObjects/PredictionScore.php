<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

use App\Domain\AIIntelligence\Exceptions\InvalidPredictionScoreException;

final readonly class PredictionScore
{
    private function __construct(
        public int $value,
    ) {}

    public static function create(int $value): self
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidPredictionScoreException;
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
