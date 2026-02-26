<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class StyleAnalysisResult
{
    /**
     * @param  array<string, mixed>  $tonePreferences
     * @param  array<string, mixed>  $lengthPreferences
     * @param  array<string, mixed>  $vocabularyPreferences
     * @param  array<string, mixed>  $structurePreferences
     * @param  array<string, mixed>  $hashtagPreferences
     */
    public function __construct(
        public array $tonePreferences,
        public array $lengthPreferences,
        public array $vocabularyPreferences,
        public array $structurePreferences,
        public array $hashtagPreferences,
        public ?string $styleSummary,
        public int $sampleSize,
    ) {}
}
