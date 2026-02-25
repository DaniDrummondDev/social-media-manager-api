<?php

declare(strict_types=1);

namespace App\Domain\Analytics\ValueObjects;

enum ExportStatus: string
{
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Expired = 'expired';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Processing => in_array($target, [self::Ready, self::Failed], true),
            self::Ready => $target === self::Expired,
            self::Failed, self::Expired => false,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Failed, self::Expired], true);
    }
}
