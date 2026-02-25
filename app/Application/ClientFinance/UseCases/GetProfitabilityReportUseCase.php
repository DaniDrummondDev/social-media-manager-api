<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\GetProfitabilityReportInput;
use App\Application\ClientFinance\DTOs\ProfitabilityReportOutput;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\ClientFinance\Repositories\CostAllocationRepositoryInterface;
use App\Domain\ClientFinance\Services\InvoiceCalculationService;
use App\Domain\ClientFinance\ValueObjects\YearMonth;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetProfitabilityReportUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
        private readonly CostAllocationRepositoryInterface $costAllocationRepository,
        private readonly InvoiceCalculationService $calculationService,
    ) {}

    public function execute(GetProfitabilityReportInput $input): ProfitabilityReportOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $month = $input->referenceMonth !== null
            ? YearMonth::fromString($input->referenceMonth)
            : null;

        if ($input->clientId !== null) {
            $clientId = Uuid::fromString($input->clientId);

            $revenueCents = $this->invoiceRepository->sumPaidByClient($clientId, $organizationId, $month);
            $costCents = $this->costAllocationRepository->sumByClient($clientId, $organizationId, $month);
        } else {
            $revenueCents = $this->invoiceRepository->sumPaidByOrganization($organizationId, $month);
            $costCents = $this->costAllocationRepository->sumByOrganization($organizationId, $month);
        }

        $profitability = $this->calculationService->calculateProfitability($revenueCents, $costCents);

        return new ProfitabilityReportOutput(
            revenueCents: $revenueCents,
            costCents: $costCents,
            profitCents: $profitability['profit_cents'],
            marginPercent: $profitability['margin_percent'],
            clientId: $input->clientId,
            referenceMonth: $input->referenceMonth,
        );
    }
}
