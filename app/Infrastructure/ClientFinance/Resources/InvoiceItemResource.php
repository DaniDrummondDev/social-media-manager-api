<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Resources;

use App\Application\ClientFinance\DTOs\InvoiceItemOutput;

final readonly class InvoiceItemResource
{
    public function __construct(
        private InvoiceItemOutput $output,
    ) {}

    public static function fromOutput(InvoiceItemOutput $output): self
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
            'description' => $this->output->description,
            'quantity' => $this->output->quantity,
            'unit_price_cents' => $this->output->unitPriceCents,
            'total_cents' => $this->output->totalCents,
            'position' => $this->output->position,
        ];
    }
}
