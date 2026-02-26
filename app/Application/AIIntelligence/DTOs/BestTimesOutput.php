<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\PostingTimeRecommendation;

final readonly class BestTimesOutput
{
    /**
     * @param  array<array<string, mixed>>  $topSlots
     * @param  array<array<string, mixed>>  $worstSlots
     */
    public function __construct(
        public array $topSlots,
        public array $worstSlots,
        public string $confidenceLevel,
        public int $sampleSize,
        public string $calculatedAt,
        public string $expiresAt,
    ) {}

    public static function fromEntity(PostingTimeRecommendation $recommendation): self
    {
        return new self(
            topSlots: array_map(fn ($slot) => $slot->toArray(), $recommendation->topSlots),
            worstSlots: array_map(fn ($slot) => $slot->toArray(), $recommendation->worstSlots),
            confidenceLevel: $recommendation->confidenceLevel->value,
            sampleSize: $recommendation->sampleSize,
            calculatedAt: $recommendation->calculatedAt->format('c'),
            expiresAt: $recommendation->expiresAt->format('c'),
        );
    }
}
