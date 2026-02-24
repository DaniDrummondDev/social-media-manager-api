<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class AISettingsUpdated extends DomainEvent
{
    public function eventName(): string
    {
        return 'ai_settings.updated';
    }
}
