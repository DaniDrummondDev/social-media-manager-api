<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class CommentCaptured extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $contentId,
        public string $provider,
        public string $authorName,
        public ?string $authorExternalId,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'engagement.comment_captured';
    }
}
