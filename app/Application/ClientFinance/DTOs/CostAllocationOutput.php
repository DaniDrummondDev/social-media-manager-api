<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

use App\Domain\ClientFinance\Entities\CostAllocation;

final readonly class CostAllocationOutput
{
    public function __construct(
        public string $id,
        public string $clientId,
        public string $organizationId,
        public string $resourceType,
        public ?string $resourceId,
        public string $description,
        public int $costCents,
        public string $currency,
        public string $allocatedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(CostAllocation $allocation): self
    {
        return new self(
            id: $allocation->id->value,
            clientId: $allocation->clientId->value,
            organizationId: $allocation->organizationId->value,
            resourceType: $allocation->resourceType->value,
            resourceId: $allocation->resourceId ? $allocation->resourceId->value : null,
            description: $allocation->description,
            costCents: $allocation->costCents,
            currency: $allocation->currency->value,
            allocatedAt: $allocation->allocatedAt->format('c'),
            createdAt: $allocation->createdAt->format('c'),
        );
    }
}
