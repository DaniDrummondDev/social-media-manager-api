<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\GenerateInvoiceInput;
use App\Application\ClientFinance\DTOs\InvoiceOutput;
use App\Application\ClientFinance\Exceptions\ClientNotFoundException;
use App\Domain\ClientFinance\Entities\ClientInvoice;
use App\Domain\ClientFinance\Entities\InvoiceItem;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\ClientFinance\ValueObjects\YearMonth;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GenerateInvoiceUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(GenerateInvoiceInput $input): InvoiceOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $clientId = Uuid::fromString($input->clientId);

        $client = $this->clientRepository->findByIdAndOrganization($clientId, $organizationId);

        if ($client === null) {
            throw new ClientNotFoundException();
        }

        $items = [];
        foreach ($input->items as $position => $itemData) {
            $items[] = InvoiceItem::create(
                description: $itemData['description'],
                quantity: $itemData['quantity'],
                unitPriceCents: $itemData['unit_price_cents'],
                position: $position + 1,
            );
        }

        $contractId = $input->contractId !== null
            ? Uuid::fromString($input->contractId)
            : null;

        $invoice = ClientInvoice::create(
            clientId: $clientId,
            contractId: $contractId,
            organizationId: $organizationId,
            referenceMonth: YearMonth::fromString($input->referenceMonth),
            items: $items,
            discountCents: $input->discountCents,
            currency: Currency::from($input->currency),
            dueDate: new DateTimeImmutable($input->dueDate),
            notes: $input->notes,
            userId: $input->userId,
        );

        $this->invoiceRepository->create($invoice);

        return InvoiceOutput::fromEntity($invoice);
    }
}
