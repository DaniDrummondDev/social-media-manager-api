<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class LearningContextUpdated extends DomainEvent
{
    /**
     * @param  array<string>  $contextTypesUpdated
     */
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public array $contextTypesUpdated,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'ai_intelligence.learning_context_updated';
    }
}
