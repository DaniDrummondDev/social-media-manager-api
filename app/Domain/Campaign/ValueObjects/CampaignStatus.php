<?php

declare(strict_types=1);

namespace App\Domain\Campaign\ValueObjects;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Archived = 'archived';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Active, self::Archived], true),
            self::Active => in_array($target, [self::Paused, self::Completed, self::Archived], true),
            self::Paused => in_array($target, [self::Active, self::Completed, self::Archived], true),
            self::Completed => $target === self::Archived,
            self::Archived => false,
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Active, self::Paused], true);
    }
}
