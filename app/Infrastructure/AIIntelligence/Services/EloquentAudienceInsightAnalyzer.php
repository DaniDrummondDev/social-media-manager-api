<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\AudienceInsightAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\AudienceInsightAnalysisResult;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use App\Infrastructure\AIIntelligence\Models\AudienceInsightModel;

final class EloquentAudienceInsightAnalyzer implements AudienceInsightAnalyzerInterface
{
    public function analyze(
        array $comments,
        InsightType $type,
        ?string $organizationId = null,
    ): AudienceInsightAnalysisResult {
        // If organization is provided and no comments, return cached insights
        if ($organizationId !== null && $comments === []) {
            return $this->getCachedInsights($organizationId, $type);
        }

        // If comments are provided, return default/empty result
        // Real-time analysis would be implemented via LLM in a future enhancement
        return $this->getDefaultInsightsForType($type);
    }

    private function getCachedInsights(string $organizationId, InsightType $type): AudienceInsightAnalysisResult
    {
        /** @var AudienceInsightModel|null $insight */
        $insight = AudienceInsightModel::query()
            ->where('organization_id', $organizationId)
            ->where('insight_type', $type->value)
            ->where('expires_at', '>', now())
            ->orderByDesc('generated_at')
            ->first();

        if ($insight === null) {
            return $this->getDefaultInsightsForType($type);
        }

        return new AudienceInsightAnalysisResult(
            insightData: $insight->insight_data ?? [],
            confidenceScore: $insight->confidence_score,
            modelUsed: 'cached',
            tokensInput: 0,
            tokensOutput: 0,
        );
    }

    private function getDefaultInsightsForType(InsightType $type): AudienceInsightAnalysisResult
    {
        $insightData = match ($type) {
            InsightType::PreferredTopics => [
                'topics' => [],
                'message' => 'No topic preferences available yet. More engagement data needed.',
            ],
            InsightType::SentimentTrends => [
                'trend' => [],
                'message' => 'No sentiment trends available yet. More engagement data needed.',
            ],
            InsightType::EngagementDrivers => [
                'drivers' => [],
                'message' => 'No engagement driver data available yet.',
            ],
            InsightType::AudiencePreferences => [
                'preferences' => [],
                'message' => 'No audience preference data available yet.',
            ],
        };

        return new AudienceInsightAnalysisResult(
            insightData: $insightData,
            confidenceScore: null,
            modelUsed: null,
            tokensInput: null,
            tokensOutput: null,
        );
    }
}
