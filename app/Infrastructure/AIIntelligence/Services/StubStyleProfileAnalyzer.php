<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\StyleAnalysisResult;

final class StubStyleProfileAnalyzer implements StyleProfileAnalyzerInterface
{
    public function analyzeEditPatterns(
        string $organizationId,
        string $generationType,
    ): StyleAnalysisResult {
        return new StyleAnalysisResult(
            tonePreferences: ['preferred' => 'casual', 'avoids' => 'formal'],
            lengthPreferences: ['avg_preferred_length' => 150, 'shortens_by_pct' => 0.15],
            vocabularyPreferences: ['added_words' => ['transformar'], 'removed_words' => ['otimizar']],
            structurePreferences: ['uses_emojis' => true, 'uses_questions' => true],
            hashtagPreferences: ['avg_count' => 8, 'style' => 'branded'],
            styleSummary: 'Estilo casual com tom conversacional, uso frequente de emojis e perguntas retóricas.',
            sampleSize: 25,
        );
    }
}
