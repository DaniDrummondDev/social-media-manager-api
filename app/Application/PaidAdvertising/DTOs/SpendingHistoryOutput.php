<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class SpendingHistoryOutput
{
    /**
     * @param  array<array{date: string, spend_cents: int, impressions: int, clicks: int, conversions: int}>  $history
     */
    public function __construct(
        public array $history,
    ) {}
}
