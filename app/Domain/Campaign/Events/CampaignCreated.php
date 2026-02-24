<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class CampaignCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public readonly string $name,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'campaign.created';
    }
}
