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

    public static function empty(): self
    {
        return new self(
            tonePreferences: ['preferred' => 'neutral', 'avoids' => []],
            lengthPreferences: ['avg_preferred_length' => 200, 'shortens_by_pct' => 0.0],
            vocabularyPreferences: ['added_words' => [], 'removed_words' => []],
            structurePreferences: ['uses_emojis' => false, 'uses_questions' => false],
            hashtagPreferences: ['avg_count' => 5, 'style' => 'standard'],
            styleSummary: null,
            sampleSize: 0,
        );
    }

    public function isEmpty(): bool
    {
        return $this->sampleSize === 0;
    }
}
