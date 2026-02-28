<?php

declare(strict_types=1);

namespace App\Domain\Publishing\ValueObjects;

enum PublishingStatus: string
{
    case Pending = 'pending';
    case Dispatched = 'dispatched';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Dispatched, self::Cancelled], true),
            self::Dispatched => $target === self::Publishing,
            self::Publishing => in_array($target, [self::Published, self::Failed], true),
            self::Failed => in_array($target, [self::Dispatched, self::Publishing], true),
            self::Published, self::Cancelled => false,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Published, self::Cancelled], true);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Dispatched, self::Publishing], true);
    }
}
