<?php

declare(strict_types=1);

namespace App\Domain\Media\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class MediaScanned extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $scanResult,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'media.scanned';
    }
}
