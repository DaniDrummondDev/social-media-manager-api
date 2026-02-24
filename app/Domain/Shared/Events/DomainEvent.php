<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

use DateTimeImmutable;

abstract class DomainEvent
{
    public readonly string $eventId;

    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $aggregateId,
        public readonly string $organizationId,
        public readonly string $userId,
    ) {
        $this->eventId = (string) \Illuminate\Support\Str::uuid();
        $this->occurredAt = new DateTimeImmutable;
    }

    abstract public function eventName(): string;
}
