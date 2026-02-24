<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class UserPasswordChanged extends DomainEvent
{
    public function eventName(): string
    {
        return 'identity.user.password_changed';
    }
}
