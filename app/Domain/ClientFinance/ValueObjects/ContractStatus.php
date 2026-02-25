<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

enum ContractStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Active => in_array($target, [self::Paused, self::Completed, self::Cancelled], true),
            self::Paused => in_array($target, [self::Active, self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }
}
