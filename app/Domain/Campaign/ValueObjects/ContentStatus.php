<?php

declare(strict_types=1);

namespace App\Domain\Campaign\ValueObjects;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => $target === self::Ready,
            self::Ready => in_array($target, [self::Draft, self::Scheduled], true),
            self::Scheduled => in_array($target, [self::Ready, self::Published], true),
            self::Published => false,
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
