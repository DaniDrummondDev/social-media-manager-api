<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class CostAllocated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $clientId,
        public int $costCents,
        public string $resourceType,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'client-finance.cost_allocated';
    }
}
