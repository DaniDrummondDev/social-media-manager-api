<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class FinancialDashboardOutput
{
    public function __construct(
        public int $totalRevenueCents,
        public int $totalCostCents,
        public int $profitCents,
        public float $marginPercent,
        public int $activeClients,
        public int $activeContracts,
        public int $overdueInvoices,
        public int $draftInvoices,
    ) {}
}
