<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class MetricsSynced extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $socialAccountId,
        public string $provider,
        public string $syncedAt,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'analytics.metrics_synced';
    }
}
