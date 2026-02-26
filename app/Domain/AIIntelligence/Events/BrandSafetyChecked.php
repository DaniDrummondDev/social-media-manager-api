<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class BrandSafetyChecked extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $contentId,
        public string $overallStatus,
        public ?int $score,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'ai_intelligence.brand_safety_checked';
    }
}
