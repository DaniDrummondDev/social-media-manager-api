<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

final readonly class StylePreferences
{
    /**
     * @param  array{preferred: string, avoids: string, detected_patterns: array<string>}  $tonePreferences
     * @param  array{avg_preferred_length: int, shortens_by_pct: float, extends_by_pct: float}  $lengthPreferences
     * @param  array{added_words: array<string>, removed_words: array<string>, preferred_phrases: array<string>}  $vocabularyPreferences
     * @param  array{uses_emojis: bool, uses_questions: bool, preferred_cta_style: string}  $structurePreferences
     * @param  array{avg_count: int, preferred_tags: array<string>, avoided_tags: array<string>, style: string}  $hashtagPreferences
     */
    private function __construct(
        public array $tonePreferences,
        public array $lengthPreferences,
        public array $vocabularyPreferences,
        public array $structurePreferences,
        public array $hashtagPreferences,
    ) {}

    /**
     * @param  array{preferred: string, avoids: string, detected_patterns: array<string>}  $tonePreferences
     * @param  array{avg_preferred_length: int, shortens_by_pct: float, extends_by_pct: float}  $lengthPreferences
     * @param  array{added_words: array<string>, removed_words: array<string>, preferred_phrases: array<string>}  $vocabularyPreferences
     * @param  array{uses_emojis: bool, uses_questions: bool, preferred_cta_style: string}  $structurePreferences
     * @param  array{avg_count: int, preferred_tags: array<string>, avoided_tags: array<string>, style: string}  $hashtagPreferences
     */
    public static function create(
        array $tonePreferences,
        array $lengthPreferences,
        array $vocabularyPreferences,
        array $structurePreferences,
        array $hashtagPreferences,
    ): self {
        return new self(
            tonePreferences: $tonePreferences,
            lengthPreferences: $lengthPreferences,
            vocabularyPreferences: $vocabularyPreferences,
            structurePreferences: $structurePreferences,
            hashtagPreferences: $hashtagPreferences,
        );
    }

    /**
     * @param  array{tone_preferences: array, length_preferences: array, vocabulary_preferences: array, structure_preferences: array, hashtag_preferences: array}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tonePreferences: $data['tone_preferences'] ?? [],
            lengthPreferences: $data['length_preferences'] ?? [],
            vocabularyPreferences: $data['vocabulary_preferences'] ?? [],
            structurePreferences: $data['structure_preferences'] ?? [],
            hashtagPreferences: $data['hashtag_preferences'] ?? [],
        );
    }

    /**
     * @return array{tone_preferences: array, length_preferences: array, vocabulary_preferences: array, structure_preferences: array, hashtag_preferences: array}
     */
    public function toArray(): array
    {
        return [
            'tone_preferences' => $this->tonePreferences,
            'length_preferences' => $this->lengthPreferences,
            'vocabulary_preferences' => $this->vocabularyPreferences,
            'structure_preferences' => $this->structurePreferences,
            'hashtag_preferences' => $this->hashtagPreferences,
        ];
    }
}
