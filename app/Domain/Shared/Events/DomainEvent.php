<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

abstract readonly class DomainEvent
{
    public readonly string $eventId;

    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $aggregateId,
        public readonly string $organizationId,
        public readonly string $userId,
    ) {
        $this->eventId = (string) Uuid::generate();
        $this->occurredAt = new DateTimeImmutable;
    }

    abstract public function eventName(): string;
}
