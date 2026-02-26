<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

use App\Application\AIIntelligence\DTOs\BrandSafetyAnalysisResult;

interface BrandSafetyAnalyzerInterface
{
    public function analyze(string $content, ?string $provider = null): BrandSafetyAnalysisResult;
}
