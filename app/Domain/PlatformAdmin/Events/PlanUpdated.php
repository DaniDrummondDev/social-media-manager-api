<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class PlanUpdated extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $planId,
        public array $changes,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'platform_admin.plan_updated';
    }
}
