<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

enum ScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Rejected = 'rejected';

    public function isUsable(): bool
    {
        return $this === self::Clean;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Clean, self::Rejected], true),
            self::Clean, self::Rejected => false,
        };
    }
}
