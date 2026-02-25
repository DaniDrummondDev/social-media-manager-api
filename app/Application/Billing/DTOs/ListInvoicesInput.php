<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class ListInvoicesInput
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
