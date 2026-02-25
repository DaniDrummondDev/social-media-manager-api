<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class MarkInvoicePaidInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $invoiceId,
        public string $paymentMethod,
        public ?string $paymentNotes = null,
    ) {}
}
