<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ListeningAlertTriggered extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $conditionType,
        public string $queryId,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'social_listening.alert_triggered';
    }
}
