<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\ValueObjects;

use App\Domain\Shared\Exceptions\DomainException;

final readonly class PerformanceScore
{
    private function __construct(
        public float $value,
    ) {}

    /**
     * Calculate performance score from feedback counters.
     * Formula: (accepted + edited × 0.7) / total_uses × 100
     */
    public static function calculate(int $totalUses, int $totalAccepted, int $totalEdited): self
    {
        if ($totalUses <= 0) {
            return new self(0.0);
        }

        $score = ($totalAccepted + $totalEdited * 0.7) / $totalUses * 100;

        return new self(round(min($score, 100.0), 2));
    }

    public static function fromFloat(float $value): self
    {
        if ($value < 0.0 || $value > 100.0) {
            throw new DomainException('Performance score must be between 0.0 and 100.0.');
        }

        return new self($value);
    }

    public function isEligibleForAutoSelection(): bool
    {
        return $this->value > 0.0;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
