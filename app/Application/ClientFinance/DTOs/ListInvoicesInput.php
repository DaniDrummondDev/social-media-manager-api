<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class ListInvoicesInput
{
    public function __construct(
        public string $organizationId,
        public ?string $clientId = null,
        public ?string $status = null,
        public ?string $referenceMonth = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
