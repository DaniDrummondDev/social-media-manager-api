<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class PromptExperimentCompleted extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $winnerId,
        public float $confidenceLevel,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'content_ai.prompt_experiment_completed';
    }
}
