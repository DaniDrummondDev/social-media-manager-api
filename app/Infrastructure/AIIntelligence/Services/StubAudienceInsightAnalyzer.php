<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\AudienceInsightAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\AudienceInsightAnalysisResult;
use App\Domain\AIIntelligence\ValueObjects\InsightType;

final class StubAudienceInsightAnalyzer implements AudienceInsightAnalyzerInterface
{
    public function analyze(array $comments, InsightType $type): AudienceInsightAnalysisResult
    {
        $insightData = match ($type) {
            InsightType::PreferredTopics => [
                'topics' => [
                    ['name' => 'Technology', 'score' => 0.85, 'comment_count' => 120],
                    ['name' => 'Design', 'score' => 0.72, 'comment_count' => 95],
                ],
            ],
            InsightType::SentimentTrends => [
                'trend' => [
                    ['period' => 'week_1', 'positive_pct' => 0.65, 'neutral_pct' => 0.25, 'negative_pct' => 0.10],
                ],
            ],
            InsightType::EngagementDrivers => [
                'drivers' => [
                    ['type' => 'question', 'description' => 'Posts with questions drive 2x engagement', 'correlation_score' => 0.78],
                ],
            ],
            InsightType::AudiencePreferences => [
                'preferences' => [
                    ['category' => 'content_format', 'value' => 'short_video', 'confidence' => 0.82],
                ],
            ],
        };

        return new AudienceInsightAnalysisResult(
            insightData: $insightData,
            confidenceScore: 0.75,
            modelUsed: 'stub',
            tokensInput: 0,
            tokensOutput: 0,
        );
    }
}
