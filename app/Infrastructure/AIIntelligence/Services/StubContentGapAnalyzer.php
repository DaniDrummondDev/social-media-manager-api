<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\ContentGapAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\ContentGapAnalysisResult;

final class StubContentGapAnalyzer implements ContentGapAnalyzerInterface
{
    public function analyze(array $ourTopics, array $competitorTopics): ContentGapAnalysisResult
    {
        return new ContentGapAnalysisResult(
            gaps: [
                [
                    'topic' => 'AI Automation',
                    'opportunity_score' => 85,
                    'competitor_count' => 3,
                    'recommendation' => 'Create content about AI automation workflows',
                ],
                [
                    'topic' => 'Remote Work Tools',
                    'opportunity_score' => 60,
                    'competitor_count' => 2,
                    'recommendation' => 'Explore remote work tool comparisons',
                ],
            ],
            opportunities: [
                [
                    'topic' => 'AI Automation',
                    'reason' => 'High competitor coverage with no presence from us',
                    'suggested_content_type' => 'tutorial',
                    'estimated_impact' => 'high',
                ],
            ],
            modelUsed: 'stub',
            tokensInput: 0,
            tokensOutput: 0,
        );
    }
}
