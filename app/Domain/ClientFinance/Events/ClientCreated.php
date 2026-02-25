<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ClientCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $clientName,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'client-finance.client_created';
    }
}
