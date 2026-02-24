<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class TwoFactorDisabled extends DomainEvent
{
    public function eventName(): string
    {
        return 'identity.user.2fa_disabled';
    }
}
