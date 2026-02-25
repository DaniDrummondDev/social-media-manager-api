<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Resources;

use App\Application\ClientFinance\DTOs\InvoiceOutput;

final readonly class InvoiceResource
{
    public function __construct(
        private InvoiceOutput $output,
    ) {}

    public static function fromOutput(InvoiceOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'invoice',
            'attributes' => [
                'client_id' => $this->output->clientId,
                'contract_id' => $this->output->contractId,
                'reference_month' => $this->output->referenceMonth,
                'items' => array_map(
                    fn ($item) => InvoiceItemResource::fromOutput($item)->toArray(),
                    $this->output->items,
                ),
                'subtotal_cents' => $this->output->subtotalCents,
                'discount_cents' => $this->output->discountCents,
                'total_cents' => $this->output->totalCents,
                'currency' => $this->output->currency,
                'status' => $this->output->status,
                'due_date' => $this->output->dueDate,
                'paid_at' => $this->output->paidAt,
                'sent_at' => $this->output->sentAt,
                'payment_method' => $this->output->paymentMethod,
                'payment_notes' => $this->output->paymentNotes,
                'notes' => $this->output->notes,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
