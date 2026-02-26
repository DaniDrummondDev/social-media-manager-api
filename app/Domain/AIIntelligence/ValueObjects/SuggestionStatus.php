<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum SuggestionStatus: string
{
    case Generating = 'generating';
    case Generated = 'generated';
    case Reviewed = 'reviewed';
    case Accepted = 'accepted';
    case Expired = 'expired';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Accepted, self::Expired => true,
            default => false,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Generating => $target === self::Generated,
            self::Generated => in_array($target, [self::Reviewed, self::Accepted, self::Expired], true),
            self::Reviewed => in_array($target, [self::Accepted, self::Expired], true),
            self::Accepted, self::Expired => false,
        };
    }
}
