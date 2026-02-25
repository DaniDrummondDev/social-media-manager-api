<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class UpdateInvoiceInput
{
    /**
     * @param  array<array{description: string, quantity: int, unit_price_cents: int}>  $items
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $invoiceId,
        public array $items,
        public int $discountCents,
        public ?string $notes = null,
        public ?string $dueDate = null,
    ) {}
}
