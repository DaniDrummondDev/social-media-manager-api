<?php

declare(strict_types=1);

namespace App\Domain\Identity\ValueObjects;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function canLogin(): bool
    {
        return $this === self::Active;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Active => in_array($target, [self::Inactive, self::Suspended], true),
            self::Inactive => $target === self::Active,
            self::Suspended => $target === self::Active,
        };
    }
}
