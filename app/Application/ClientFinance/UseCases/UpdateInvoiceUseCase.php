<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\InvoiceOutput;
use App\Application\ClientFinance\DTOs\UpdateInvoiceInput;
use App\Application\ClientFinance\Exceptions\InvoiceNotFoundException;
use App\Domain\ClientFinance\Entities\InvoiceItem;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class UpdateInvoiceUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(UpdateInvoiceInput $input): InvoiceOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $invoiceId = Uuid::fromString($input->invoiceId);

        $invoice = $this->invoiceRepository->findByIdAndOrganization($invoiceId, $organizationId);

        if ($invoice === null) {
            throw new InvoiceNotFoundException();
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

        $dueDate = $input->dueDate !== null
            ? new DateTimeImmutable($input->dueDate)
            : null;

        $invoice = $invoice->updateDraft(
            items: $items,
            discountCents: $input->discountCents,
            notes: $input->notes,
            dueDate: $dueDate,
        );

        $this->invoiceRepository->update($invoice);

        return InvoiceOutput::fromEntity($invoice);
    }
}
