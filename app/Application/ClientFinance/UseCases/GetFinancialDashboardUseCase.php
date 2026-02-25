<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\FinancialDashboardOutput;
use App\Application\ClientFinance\DTOs\GetFinancialDashboardInput;
use App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\ClientFinance\Repositories\CostAllocationRepositoryInterface;
use App\Domain\ClientFinance\Services\InvoiceCalculationService;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetFinancialDashboardUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly ClientContractRepositoryInterface $contractRepository,
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
        private readonly CostAllocationRepositoryInterface $costAllocationRepository,
        private readonly InvoiceCalculationService $calculationService,
    ) {}

    public function execute(GetFinancialDashboardInput $input): FinancialDashboardOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $activeClientsResult = $this->clientRepository->findByOrganization(
            organizationId: $organizationId,
            status: 'active',
        );
        $activeClients = count($activeClientsResult['items']);

        $activeContracts = count($this->contractRepository->findActiveByOrganization($organizationId));

        $overdueResult = $this->invoiceRepository->findByOrganization(
            organizationId: $organizationId,
            status: 'overdue',
        );
        $overdueInvoices = count($overdueResult['items']);

        $draftResult = $this->invoiceRepository->findByOrganization(
            organizationId: $organizationId,
            status: 'draft',
        );
        $draftInvoices = count($draftResult['items']);

        $totalRevenueCents = $this->invoiceRepository->sumPaidByOrganization($organizationId);
        $totalCostCents = $this->costAllocationRepository->sumByOrganization($organizationId);

        $profitability = $this->calculationService->calculateProfitability($totalRevenueCents, $totalCostCents);

        return new FinancialDashboardOutput(
            totalRevenueCents: $totalRevenueCents,
            totalCostCents: $totalCostCents,
            profitCents: $profitability['profit_cents'],
            marginPercent: $profitability['margin_percent'],
            activeClients: $activeClients,
            activeContracts: $activeContracts,
            overdueInvoices: $overdueInvoices,
            draftInvoices: $draftInvoices,
        );
    }
}
