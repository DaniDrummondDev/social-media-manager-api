<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

use App\Application\AIIntelligence\DTOs\MentionAnalysisResult;

interface MentionAnalyzerInterface
{
    /**
     * Analyze a social mention via multi-agent Social Listening pipeline.
     *
     * @param  array<string, mixed>  $mention
     * @param  array<string, mixed>  $brandContext
     */
    public function analyzeMention(
        string $organizationId,
        array $mention,
        array $brandContext,
        string $language = 'pt-BR',
    ): MentionAnalysisResult;
}
