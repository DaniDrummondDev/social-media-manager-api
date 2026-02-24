<?php

declare(strict_types=1);

namespace App\Domain\Organization\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class MemberRoleChanged extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $oldRole,
        public string $newRole,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'organization.member.role_changed';
    }
}
