<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class UserBanned extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $targetUserId,
        public string $reason,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'platform_admin.user_banned';
    }
}
