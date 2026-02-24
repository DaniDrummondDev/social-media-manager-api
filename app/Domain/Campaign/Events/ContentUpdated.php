<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ContentUpdated extends DomainEvent
{
    public function eventName(): string
    {
        return 'content.updated';
    }
}
