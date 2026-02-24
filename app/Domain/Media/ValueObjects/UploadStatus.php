<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

enum UploadStatus: string
{
    case Initiated = 'initiated';
    case Uploading = 'uploading';
    case Completing = 'completing';
    case Completed = 'completed';
    case Aborted = 'aborted';
    case Expired = 'expired';

    public function isActive(): bool
    {
        return in_array($this, [self::Initiated, self::Uploading, self::Completing], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Aborted, self::Expired], true);
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Initiated => in_array($target, [self::Uploading, self::Aborted, self::Expired], true),
            self::Uploading => in_array($target, [self::Completing, self::Aborted, self::Expired], true),
            self::Completing => in_array($target, [self::Completed, self::Aborted], true),
            self::Completed, self::Aborted, self::Expired => false,
        };
    }
}
