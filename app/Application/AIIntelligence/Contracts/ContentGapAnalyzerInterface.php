<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

use App\Application\AIIntelligence\DTOs\ContentGapAnalysisResult;

interface ContentGapAnalyzerInterface
{
    /**
     * Analyze our content topics against competitor topics to identify gaps and opportunities.
     *
     * @param  array<array{topic: string, frequency: int, avg_engagement: float}>  $ourTopics
     * @param  array<array{topic: string, source_competitor: string, frequency: int, avg_engagement: float}>  $competitorTopics
     */
    public function analyze(array $ourTopics, array $competitorTopics): ContentGapAnalysisResult;
}
