<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum ProfileStatus: string
{
    case Generating = 'generating';
    case Generated = 'generated';
    case Expired = 'expired';

    public function isFinal(): bool
    {
        return $this === self::Expired;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Generating => $target === self::Generated,
            self::Generated => $target === self::Expired,
            self::Expired => false,
        };
    }
}
