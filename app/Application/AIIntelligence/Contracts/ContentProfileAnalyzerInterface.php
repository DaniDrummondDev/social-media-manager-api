<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

use App\Application\AIIntelligence\DTOs\ContentProfileResult;

interface ContentProfileAnalyzerInterface
{
    /**
     * Analyze published content profile via multi-agent Content DNA pipeline.
     *
     * @param  array<int, array<string, mixed>>  $publishedContents
     * @param  array<int, array<string, mixed>>  $metrics
     * @param  array<string, mixed>|null  $currentStyleProfile
     */
    public function analyzeProfile(
        string $organizationId,
        array $publishedContents,
        array $metrics,
        ?array $currentStyleProfile = null,
        string $timeWindow = 'last_90_days',
    ): ContentProfileResult;
}
