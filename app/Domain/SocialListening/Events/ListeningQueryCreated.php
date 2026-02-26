<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ListeningQueryCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $queryType,
        public string $value,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'social_listening.query_created';
    }
}
