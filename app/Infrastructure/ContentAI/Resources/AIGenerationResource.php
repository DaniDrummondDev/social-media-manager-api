<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Resources;

use App\Application\ContentAI\DTOs\AIGenerationOutput;

final readonly class AIGenerationResource
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $output
     */
    private function __construct(
        private string $id,
        private string $type,
        private array $input,
        private array $output,
        private int $tokensInput,
        private int $tokensOutput,
        private string $model,
        private float $costEstimate,
        private int $durationMs,
        private string $createdAt,
    ) {}

    public static function fromOutput(AIGenerationOutput $output): self
    {
        return new self(
            id: $output->id,
            type: $output->type,
            input: $output->input,
            output: $output->output,
            tokensInput: $output->tokensInput,
            tokensOutput: $output->tokensOutput,
            model: $output->model,
            costEstimate: $output->costEstimate,
            durationMs: $output->durationMs,
            createdAt: $output->createdAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'ai_generation',
            'attributes' => [
                'generation_type' => $this->type,
                'input' => $this->input,
                'output' => $this->output,
                'model' => $this->model,
                'tokens_input' => $this->tokensInput,
                'tokens_output' => $this->tokensOutput,
                'cost_estimate_usd' => $this->costEstimate,
                'duration_ms' => $this->durationMs,
                'created_at' => $this->createdAt,
            ],
        ];
    }
}
