<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class MentionFlagged extends DomainEvent
{
    public function eventName(): string
    {
        return 'social_listening.mention_flagged';
    }
}
