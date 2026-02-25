<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\GenerateMonthlyInvoicesInput;
use App\Domain\ClientFinance\Entities\ClientInvoice;
use App\Domain\ClientFinance\Entities\InvoiceItem;
use App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\YearMonth;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GenerateMonthlyInvoicesUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
        private readonly ClientContractRepositoryInterface $contractRepository,
    ) {}

    public function execute(GenerateMonthlyInvoicesInput $input): int
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $referenceMonth = YearMonth::fromString($input->referenceMonth);

        $contracts = $this->contractRepository->findActiveByOrganization($organizationId);

        $generated = 0;

        foreach ($contracts as $contract) {
            $existing = $this->invoiceRepository->findByContractAndMonth(
                $contract->id,
                $referenceMonth,
            );

            if ($existing !== null) {
                continue;
            }

            $item = InvoiceItem::create(
                description: $contract->name,
                quantity: 1,
                unitPriceCents: $contract->valueCents,
                position: 1,
            );

            $dueDate = $referenceMonth->endOfMonth();

            $invoice = ClientInvoice::create(
                clientId: $contract->clientId,
                contractId: $contract->id,
                organizationId: $organizationId,
                referenceMonth: $referenceMonth,
                items: [$item],
                discountCents: 0,
                currency: $contract->currency,
                dueDate: $dueDate,
                notes: null,
                userId: $input->userId,
            );

            $this->invoiceRepository->create($invoice);
            $generated++;
        }

        return $generated;
    }
}
