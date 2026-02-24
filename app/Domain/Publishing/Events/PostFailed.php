<?php

declare(strict_types=1);

namespace App\Domain\Publishing\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class PostFailed extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $contentId,
        public string $socialAccountId,
        public string $errorCode,
        public bool $isPermanent,
        public int $attempts,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'publishing.post_failed';
    }
}
