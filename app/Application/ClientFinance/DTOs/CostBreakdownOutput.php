<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class CostBreakdownOutput
{
    /**
     * @param  array<CostAllocationOutput>  $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {}
}
