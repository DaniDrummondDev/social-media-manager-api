<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

use App\Domain\ContentAI\Entities\PromptExperiment;

final readonly class PromptExperimentOutput
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $generationType,
        public string $name,
        public string $status,
        public string $variantAId,
        public string $variantBId,
        public float $trafficSplit,
        public int $minSampleSize,
        public int $variantAUses,
        public int $variantAAccepted,
        public int $variantBUses,
        public int $variantBAccepted,
        public ?string $winnerId,
        public ?float $confidenceLevel,
        public float $acceptanceRateA,
        public float $acceptanceRateB,
        public ?string $startedAt,
        public ?string $completedAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(PromptExperiment $experiment): self
    {
        return new self(
            id: (string) $experiment->id,
            organizationId: (string) $experiment->organizationId,
            generationType: $experiment->generationType,
            name: $experiment->name,
            status: $experiment->status->value,
            variantAId: (string) $experiment->variantAId,
            variantBId: (string) $experiment->variantBId,
            trafficSplit: $experiment->trafficSplit,
            minSampleSize: $experiment->minSampleSize,
            variantAUses: $experiment->variantAUses,
            variantAAccepted: $experiment->variantAAccepted,
            variantBUses: $experiment->variantBUses,
            variantBAccepted: $experiment->variantBAccepted,
            winnerId: $experiment->winnerId !== null ? (string) $experiment->winnerId : null,
            confidenceLevel: $experiment->confidenceLevel,
            acceptanceRateA: $experiment->getAcceptanceRateA(),
            acceptanceRateB: $experiment->getAcceptanceRateB(),
            startedAt: $experiment->startedAt?->format('c'),
            completedAt: $experiment->completedAt?->format('c'),
            createdAt: $experiment->createdAt->format('c'),
            updatedAt: $experiment->updatedAt->format('c'),
        );
    }
}
