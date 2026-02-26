<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\ContentProfile;

final readonly class ContentProfileOutput
{
    /**
     * @param  array<array{theme: string, score: float, content_count: int}>  $topThemes
     * @param  array<string, mixed>  $engagementPatterns
     * @param  array<string, mixed>  $contentFingerprint
     * @param  array<string, mixed>  $highPerformerTraits
     */
    public function __construct(
        public string $id,
        public ?string $provider,
        public int $totalContentsAnalyzed,
        public array $topThemes,
        public array $engagementPatterns,
        public array $contentFingerprint,
        public array $highPerformerTraits,
        public string $generatedAt,
        public string $expiresAt,
    ) {}

    public static function fromEntity(ContentProfile $profile): self
    {
        return new self(
            id: (string) $profile->id,
            provider: $profile->provider,
            totalContentsAnalyzed: $profile->totalContentsAnalyzed,
            topThemes: $profile->topThemes,
            engagementPatterns: $profile->engagementPatterns?->toArray() ?? [],
            contentFingerprint: $profile->contentFingerprint?->toArray() ?? [],
            highPerformerTraits: $profile->highPerformerTraits,
            generatedAt: $profile->generatedAt->format('c'),
            expiresAt: $profile->expiresAt->format('c'),
        );
    }
}
