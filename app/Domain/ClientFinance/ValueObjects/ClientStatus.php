<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

enum ClientStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
