<?php

declare(strict_types=1);

namespace App\Domain\Organization\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class MemberRemoved extends DomainEvent
{
    public function eventName(): string
    {
        return 'organization.member.removed';
    }
}
