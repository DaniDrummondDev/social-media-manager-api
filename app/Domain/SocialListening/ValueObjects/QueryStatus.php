<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\ValueObjects;

enum QueryStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Deleted = 'deleted';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Active => in_array($target, [self::Paused, self::Deleted], true),
            self::Paused => in_array($target, [self::Active, self::Deleted], true),
            self::Deleted => false,
        };
    }
}
