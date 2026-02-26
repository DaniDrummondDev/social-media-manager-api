<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class BackfillEmbeddingsOutput
{
    public function __construct(
        public int $totalItems,
        public int $successCount,
        public int $failedCount,
        public string $model,
    ) {}
}
