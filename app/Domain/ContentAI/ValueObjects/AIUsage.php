<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\ValueObjects;

final readonly class AIUsage
{
    public function __construct(
        public int $tokensInput,
        public int $tokensOutput,
        public string $model,
        public float $costEstimate,
        public int $durationMs,
    ) {}

    /**
     * @return array{tokens_input: int, tokens_output: int, model: string, cost_estimate: float, duration_ms: int}
     */
    public function toArray(): array
    {
        return [
            'tokens_input' => $this->tokensInput,
            'tokens_output' => $this->tokensOutput,
            'model' => $this->model,
            'cost_estimate' => $this->costEstimate,
            'duration_ms' => $this->durationMs,
        ];
    }
}
