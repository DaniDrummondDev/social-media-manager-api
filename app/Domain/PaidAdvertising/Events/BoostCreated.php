<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class BoostCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $scheduledPostId,
        public string $audienceId,
        public string $objective,
        public int $budgetCents,
        public string $currency,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'paid_advertising.boost_created';
    }
}
