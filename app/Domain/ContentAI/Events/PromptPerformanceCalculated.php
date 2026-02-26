<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class PromptPerformanceCalculated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public float $performanceScore,
        public int $totalUses,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'content_ai.prompt_performance_calculated';
    }
}
