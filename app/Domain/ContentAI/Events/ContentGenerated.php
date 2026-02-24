<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ContentGenerated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public readonly string $generationType,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'content.generated';
    }
}
