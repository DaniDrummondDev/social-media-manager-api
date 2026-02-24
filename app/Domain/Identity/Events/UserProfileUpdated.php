<?php

declare(strict_types=1);

namespace App\Domain\Identity\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class UserProfileUpdated extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public array $changes,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'identity.user.profile_updated';
    }
}
