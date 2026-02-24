<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ContentDeleted extends DomainEvent
{
    public function eventName(): string
    {
        return 'content.deleted';
    }
}
