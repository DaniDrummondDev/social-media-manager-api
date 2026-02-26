<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class AcceptCalendarItemsInput
{
    /**
     * @param  array<int>  $acceptedIndexes
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $suggestionId,
        public array $acceptedIndexes,
    ) {}
}
