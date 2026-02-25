<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

use App\Domain\Billing\Entities\Invoice;

final readonly class InvoiceOutput
{
    public function __construct(
        public string $id,
        public int $amountCents,
        public string $currency,
        public string $status,
        public ?string $invoiceUrl,
        public string $periodStart,
        public string $periodEnd,
        public ?string $paidAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(Invoice $invoice): self
    {
        return new self(
            id: (string) $invoice->id,
            amountCents: $invoice->amount->amountCents,
            currency: $invoice->amount->currency,
            status: $invoice->status->value,
            invoiceUrl: $invoice->invoiceUrl,
            periodStart: $invoice->periodStart->format('c'),
            periodEnd: $invoice->periodEnd->format('c'),
            paidAt: $invoice->paidAt?->format('c'),
            createdAt: $invoice->createdAt->format('c'),
        );
    }
}
