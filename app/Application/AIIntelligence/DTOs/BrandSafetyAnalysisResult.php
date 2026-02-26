<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class BrandSafetyAnalysisResult
{
    /**
     * @param  array<array<string, mixed>>  $checks
     */
    public function __construct(
        public int $score,
        public array $checks,
        public ?string $modelUsed,
        public ?int $tokensInput,
        public ?int $tokensOutput,
    ) {}
}
