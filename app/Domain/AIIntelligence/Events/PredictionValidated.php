<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class PredictionValidated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $predictionId,
        public int $predictedScore,
        public int $actualNormalizedScore,
        public int $absoluteError,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'ai_intelligence.prediction_validated';
    }
}
