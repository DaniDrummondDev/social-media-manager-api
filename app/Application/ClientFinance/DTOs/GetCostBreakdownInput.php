<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class GetCostBreakdownInput
{
    public function __construct(
        public string $organizationId,
        public ?string $clientId = null,
        public ?string $resourceType = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
