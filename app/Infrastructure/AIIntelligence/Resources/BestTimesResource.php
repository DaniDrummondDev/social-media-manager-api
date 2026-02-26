<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\BestTimesOutput;

final readonly class BestTimesResource
{
    public function __construct(
        private BestTimesOutput $output,
    ) {}

    public static function fromOutput(BestTimesOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'top_slots' => $this->output->topSlots,
            'worst_slots' => $this->output->worstSlots,
            'confidence_level' => $this->output->confidenceLevel,
            'sample_size' => $this->output->sampleSize,
            'calculated_at' => $this->output->calculatedAt,
            'expires_at' => $this->output->expiresAt,
        ];
    }
}
