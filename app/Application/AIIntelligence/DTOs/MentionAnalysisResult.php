<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class MentionAnalysisResult
{
    /**
     * @param  array<string, mixed>  $output
     */
    public function __construct(
        public array $output,
        public int $tokensInput,
        public int $tokensOutput,
        public string $model,
        public int $durationMs,
        public float $costEstimate,
    ) {}
}
