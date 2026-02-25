<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class AllocateCostInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $clientId,
        public string $resourceType,
        public ?string $resourceId = null,
        public string $description = '',
        public int $costCents = 0,
        public string $currency = 'BRL',
    ) {}
}
