<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Resources;

use App\Application\Billing\DTOs\InvoiceOutput;

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
                'amount_cents' => $this->output->amountCents,
                'currency' => $this->output->currency,
                'status' => $this->output->status,
                'invoice_url' => $this->output->invoiceUrl,
                'period_start' => $this->output->periodStart,
                'period_end' => $this->output->periodEnd,
                'paid_at' => $this->output->paidAt,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
