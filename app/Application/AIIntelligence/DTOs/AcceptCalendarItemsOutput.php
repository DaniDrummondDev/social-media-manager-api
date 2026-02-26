<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class AcceptCalendarItemsOutput
{
    public function __construct(
        public string $id,
        public string $status,
        public int $acceptedCount,
        public int $totalCount,
    ) {}
}
