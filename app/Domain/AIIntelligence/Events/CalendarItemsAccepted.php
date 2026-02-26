<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class CalendarItemsAccepted extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public int $acceptedCount,
        public int $totalCount,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'ai_intelligence.calendar_items_accepted';
    }
}
