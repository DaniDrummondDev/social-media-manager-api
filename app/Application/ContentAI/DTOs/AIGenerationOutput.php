<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

use App\Domain\ContentAI\Entities\AIGeneration;

final readonly class AIGenerationOutput
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $output
     */
    public function __construct(
        public string $id,
        public string $type,
        public array $input,
        public array $output,
        public int $tokensInput,
        public int $tokensOutput,
        public string $model,
        public float $costEstimate,
        public int $durationMs,
        public string $createdAt,
    ) {}

    public static function fromEntity(AIGeneration $generation): self
    {
        return new self(
            id: (string) $generation->id,
            type: $generation->type->value,
            input: $generation->input,
            output: $generation->output,
            tokensInput: $generation->usage->tokensInput,
            tokensOutput: $generation->usage->tokensOutput,
            model: $generation->usage->model,
            costEstimate: $generation->usage->costEstimate,
            durationMs: $generation->usage->durationMs,
            createdAt: $generation->createdAt->format('c'),
        );
    }
}
