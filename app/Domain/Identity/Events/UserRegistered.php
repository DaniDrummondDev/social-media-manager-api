<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class UserRegistered extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $email,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'identity.user.registered';
    }
}
