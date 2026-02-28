<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

use App\Application\AIIntelligence\DTOs\AudienceInsightAnalysisResult;
use App\Domain\AIIntelligence\ValueObjects\InsightType;

interface AudienceInsightAnalyzerInterface
{
    /**
     * Analyze comments to extract audience insights of a given type.
     *
     * When organizationId is provided and comments are empty, cached insights from
     * the database will be returned if available. When comments are provided,
     * real-time analysis is performed.
     *
     * @param  array<array{text: string, sentiment: ?string, created_at: string}>  $comments
     */
    public function analyze(
        array $comments,
        InsightType $type,
        ?string $organizationId = null,
    ): AudienceInsightAnalysisResult;
}
