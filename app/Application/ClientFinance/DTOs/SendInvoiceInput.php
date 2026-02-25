<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class SendInvoiceInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $invoiceId,
    ) {}
}
