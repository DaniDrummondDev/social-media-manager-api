<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class BackfillEmbeddingsInput
{
    /**
     * @param  array<array{entity_type: string, entity_id: string, text: string}>  $items
     */
    public function __construct(
        public string $organizationId,
        public array $items,
        public string $userId,
    ) {}
}
