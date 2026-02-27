<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class AdMetricsSynced extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $boostId,
        public string $period,
        public int $impressions,
        public int $clicks,
        public int $spendCents,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'paid_advertising.ad_metrics_synced';
    }
}
