<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\StyleAnalysisResult;
use App\Infrastructure\AIIntelligence\Models\OrgStyleProfileModel;

final class EloquentStyleProfileAnalyzer implements StyleProfileAnalyzerInterface
{
    public function analyzeEditPatterns(
        string $organizationId,
        string $generationType,
    ): StyleAnalysisResult {
        /** @var OrgStyleProfileModel|null $profile */
        $profile = OrgStyleProfileModel::query()
            ->where('organization_id', $organizationId)
            ->where('generation_type', $generationType)
            ->where('expires_at', '>', now())
            ->orderByDesc('generated_at')
            ->first();

        if ($profile === null) {
            return $this->getDefaultStyleAnalysisResult();
        }

        return new StyleAnalysisResult(
            tonePreferences: $profile->tone_preferences ?? $this->getDefaultTonePreferences(),
            lengthPreferences: $profile->length_preferences ?? $this->getDefaultLengthPreferences(),
            vocabularyPreferences: $profile->vocabulary_preferences ?? $this->getDefaultVocabularyPreferences(),
            structurePreferences: $profile->structure_preferences ?? $this->getDefaultStructurePreferences(),
            hashtagPreferences: $profile->hashtag_preferences ?? $this->getDefaultHashtagPreferences(),
            styleSummary: $profile->style_summary,
            sampleSize: $profile->sample_size ?? 0,
        );
    }

    private function getDefaultStyleAnalysisResult(): StyleAnalysisResult
    {
        return new StyleAnalysisResult(
            tonePreferences: $this->getDefaultTonePreferences(),
            lengthPreferences: $this->getDefaultLengthPreferences(),
            vocabularyPreferences: $this->getDefaultVocabularyPreferences(),
            structurePreferences: $this->getDefaultStructurePreferences(),
            hashtagPreferences: $this->getDefaultHashtagPreferences(),
            styleSummary: null,
            sampleSize: 0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultTonePreferences(): array
    {
        return [
            'preferred' => 'neutral',
            'avoids' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultLengthPreferences(): array
    {
        return [
            'avg_preferred_length' => 200,
            'shortens_by_pct' => 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultVocabularyPreferences(): array
    {
        return [
            'added_words' => [],
            'removed_words' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultStructurePreferences(): array
    {
        return [
            'uses_emojis' => false,
            'uses_questions' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultHashtagPreferences(): array
    {
        return [
            'avg_count' => 5,
            'style' => 'standard',
        ];
    }
}
