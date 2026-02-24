<?php

declare(strict_types=1);

namespace App\Domain\Media\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class MediaRestored extends DomainEvent
{
    public function eventName(): string
    {
        return 'media.restored';
    }
}
