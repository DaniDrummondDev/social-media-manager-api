<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class GenerateInvoiceInput
{
    /**
     * @param  array<array{description: string, quantity: int, unit_price_cents: int}>  $items
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $clientId,
        public ?string $contractId,
        public string $referenceMonth,
        public array $items,
        public int $discountCents,
        public string $currency,
        public string $dueDate,
        public ?string $notes = null,
    ) {}
}
