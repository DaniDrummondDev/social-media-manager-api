<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ClientUpdated extends DomainEvent
{
    public function eventName(): string
    {
        return 'client-finance.client_updated';
    }
}
