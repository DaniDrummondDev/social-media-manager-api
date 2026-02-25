<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

use App\Domain\ClientFinance\Entities\ClientInvoice;

final readonly class InvoiceOutput
{
    /**
     * @param  array<InvoiceItemOutput>  $items
     */
    public function __construct(
        public string $id,
        public string $clientId,
        public ?string $contractId,
        public string $organizationId,
        public string $referenceMonth,
        public array $items,
        public int $subtotalCents,
        public int $discountCents,
        public int $totalCents,
        public string $currency,
        public string $status,
        public string $dueDate,
        public ?string $paidAt,
        public ?string $sentAt,
        public ?string $paymentMethod,
        public ?string $paymentNotes,
        public ?string $notes,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(ClientInvoice $invoice): self
    {
        return new self(
            id: $invoice->id->value,
            clientId: $invoice->clientId->value,
            contractId: $invoice->contractId ? $invoice->contractId->value : null,
            organizationId: $invoice->organizationId->value,
            referenceMonth: $invoice->referenceMonth->toString(),
            items: array_map(
                fn ($item) => InvoiceItemOutput::fromEntity($item),
                $invoice->items,
            ),
            subtotalCents: $invoice->subtotalCents,
            discountCents: $invoice->discountCents,
            totalCents: $invoice->totalCents,
            currency: $invoice->currency->value,
            status: $invoice->status->value,
            dueDate: $invoice->dueDate->format('c'),
            paidAt: $invoice->paidAt?->format('c'),
            sentAt: $invoice->sentAt?->format('c'),
            paymentMethod: $invoice->paymentMethod?->value,
            paymentNotes: $invoice->paymentNotes,
            notes: $invoice->notes,
            createdAt: $invoice->createdAt->format('c'),
            updatedAt: $invoice->updatedAt->format('c'),
        );
    }
}
