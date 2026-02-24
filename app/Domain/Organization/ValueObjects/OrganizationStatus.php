<?php

declare(strict_types=1);

namespace App\Domain\Organization\ValueObjects;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Deleted = 'deleted';

    public function canOperate(): bool
    {
        return $this === self::Active;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Active => in_array($target, [self::Suspended, self::Deleted], true),
            self::Suspended => in_array($target, [self::Active, self::Deleted], true),
            self::Deleted => false,
        };
    }
}
