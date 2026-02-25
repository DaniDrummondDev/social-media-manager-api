<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

use App\Domain\ClientFinance\Entities\InvoiceItem;

final readonly class InvoiceItemOutput
{
    public function __construct(
        public string $id,
        public string $description,
        public int $quantity,
        public int $unitPriceCents,
        public int $totalCents,
        public int $position,
    ) {}

    public static function fromEntity(InvoiceItem $item): self
    {
        return new self(
            id: $item->id->value,
            description: $item->description,
            quantity: $item->quantity,
            unitPriceCents: $item->unitPriceCents,
            totalCents: $item->totalCents,
            position: $item->position,
        );
    }
}
