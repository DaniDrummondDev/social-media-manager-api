<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\OrgStyleProfile;

final readonly class StyleProfileOutput
{
    /**
     * @param  array<string, mixed>  $tonePreferences
     * @param  array<string, mixed>  $lengthPreferences
     * @param  array<string, mixed>  $vocabularyPreferences
     * @param  array<string, mixed>  $structurePreferences
     * @param  array<string, mixed>  $hashtagPreferences
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $generationType,
        public int $sampleSize,
        public string $confidenceLevel,
        public array $tonePreferences,
        public array $lengthPreferences,
        public array $vocabularyPreferences,
        public array $structurePreferences,
        public array $hashtagPreferences,
        public ?string $styleSummary,
        public string $generatedAt,
        public string $expiresAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(OrgStyleProfile $profile): self
    {
        return new self(
            id: (string) $profile->id,
            organizationId: (string) $profile->organizationId,
            generationType: $profile->generationType,
            sampleSize: $profile->sampleSize,
            confidenceLevel: $profile->confidenceLevel->value,
            tonePreferences: $profile->stylePreferences->tonePreferences,
            lengthPreferences: $profile->stylePreferences->lengthPreferences,
            vocabularyPreferences: $profile->stylePreferences->vocabularyPreferences,
            structurePreferences: $profile->stylePreferences->structurePreferences,
            hashtagPreferences: $profile->stylePreferences->hashtagPreferences,
            styleSummary: $profile->styleSummary,
            generatedAt: $profile->generatedAt->format('c'),
            expiresAt: $profile->expiresAt->format('c'),
            createdAt: $profile->createdAt->format('c'),
        );
    }
}
