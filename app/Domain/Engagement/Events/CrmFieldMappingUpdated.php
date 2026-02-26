<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class CrmFieldMappingUpdated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $connectionId,
        public string $provider,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'engagement.crm_field_mapping_updated';
    }
}
