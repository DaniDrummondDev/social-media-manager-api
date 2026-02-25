<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class ProfitabilityReportOutput
{
    public function __construct(
        public int $revenueCents,
        public int $costCents,
        public int $profitCents,
        public float $marginPercent,
        public ?string $clientId,
        public ?string $referenceMonth,
    ) {}
}
