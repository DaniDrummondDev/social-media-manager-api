<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class BrandSafetyBlocked extends DomainEvent
{
    /**
     * @param  array<string>  $blockedCategories
     */
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $contentId,
        public array $blockedCategories,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'ai_intelligence.brand_safety_blocked';
    }
}
