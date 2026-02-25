<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Entities;

use App\Domain\Shared\ValueObjects\Uuid;

final readonly class InvoiceItem
{
    public function __construct(
        public Uuid $id,
        public string $description,
        public int $quantity,
        public int $unitPriceCents,
        public int $totalCents,
        public int $position,
    ) {}

    public static function create(
        string $description,
        int $quantity,
        int $unitPriceCents,
        int $position,
    ): self {
        return new self(
            id: Uuid::generate(),
            description: $description,
            quantity: $quantity,
            unitPriceCents: $unitPriceCents,
            totalCents: $quantity * $unitPriceCents,
            position: $position,
        );
    }

    public static function reconstitute(
        Uuid $id,
        string $description,
        int $quantity,
        int $unitPriceCents,
        int $totalCents,
        int $position,
    ): self {
        return new self(
            id: $id,
            description: $description,
            quantity: $quantity,
            unitPriceCents: $unitPriceCents,
            totalCents: $totalCents,
            position: $position,
        );
    }
}
