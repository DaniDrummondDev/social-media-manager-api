<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class UserEmailVerified extends DomainEvent
{
    public function eventName(): string
    {
        return 'identity.user.email_verified';
    }
}
