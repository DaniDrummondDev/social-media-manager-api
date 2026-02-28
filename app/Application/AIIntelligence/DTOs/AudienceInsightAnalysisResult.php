<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class AudienceInsightAnalysisResult
{
    /**
     * @param  array<string, mixed>  $insightData
     */
    public function __construct(
        public array $insightData,
        public ?float $confidenceScore,
        public ?string $modelUsed,
        public ?int $tokensInput,
        public ?int $tokensOutput,
    ) {}

    public static function empty(): self
    {
        return new self(
            insightData: [],
            confidenceScore: null,
            modelUsed: null,
            tokensInput: null,
            tokensOutput: null,
        );
    }

    public function isEmpty(): bool
    {
        return $this->insightData === [] || isset($this->insightData['message']);
    }
}
