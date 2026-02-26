<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\BrandSafetyAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\BrandSafetyAnalysisResult;

final class StubBrandSafetyAnalyzer implements BrandSafetyAnalyzerInterface
{
    public function analyze(string $content, ?string $provider = null): BrandSafetyAnalysisResult
    {
        return new BrandSafetyAnalysisResult(
            score: 100,
            checks: [
                ['category' => 'lgpd_compliance', 'status' => 'passed', 'message' => null, 'severity' => null],
                ['category' => 'advertising_disclosure', 'status' => 'passed', 'message' => null, 'severity' => null],
                ['category' => 'platform_policy', 'status' => 'passed', 'message' => null, 'severity' => null],
                ['category' => 'sensitivity', 'status' => 'passed', 'message' => null, 'severity' => null],
                ['category' => 'profanity', 'status' => 'passed', 'message' => null, 'severity' => null],
            ],
            modelUsed: null,
            tokensInput: null,
            tokensOutput: null,
        );
    }
}
