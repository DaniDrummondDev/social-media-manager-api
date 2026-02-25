<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class ListContractsInput
{
    public function __construct(
        public string $organizationId,
        public string $clientId,
        public ?string $status = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
