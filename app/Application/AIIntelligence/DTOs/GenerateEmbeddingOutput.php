<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateEmbeddingOutput
{
    /**
     * @param  array<float>  $embedding
     */
    public function __construct(
        public string $entityType,
        public string $entityId,
        public array $embedding,
        public int $dimensions,
        public string $model,
    ) {}
}
