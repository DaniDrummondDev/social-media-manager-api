<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

use App\Application\AIIntelligence\DTOs\StyleAnalysisResult;

interface StyleProfileAnalyzerInterface
{
    /**
     * Analyze edit patterns from generation feedback to extract style preferences.
     *
     * Requires minimum 10 edited feedbacks for the given generation type.
     * Generates style_summary via LLM (max 200 tokens, GPT-4o-mini).
     */
    public function analyzeEditPatterns(
        string $organizationId,
        string $generationType,
    ): StyleAnalysisResult;
}
