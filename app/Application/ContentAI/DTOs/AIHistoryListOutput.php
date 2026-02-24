<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class AIHistoryListOutput
{
    /**
     * @param  AIGenerationOutput[]  $items
     */
    public function __construct(
        public array $items,
    ) {}
}
