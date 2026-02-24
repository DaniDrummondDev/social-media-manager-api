<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class TokenExpired extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $provider,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'social_account.token_expired';
    }
}
