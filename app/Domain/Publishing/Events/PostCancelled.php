<?php

declare(strict_types=1);

namespace App\Domain\Publishing\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class PostCancelled extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $contentId,
        public string $socialAccountId,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'publishing.post_cancelled';
    }
}
