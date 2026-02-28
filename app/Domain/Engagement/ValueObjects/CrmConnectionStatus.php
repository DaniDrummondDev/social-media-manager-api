<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

enum CrmConnectionStatus: string
{
    case Connected = 'connected';
    case TokenExpired = 'token_expired';
    case Revoked = 'revoked';
    case Error = 'error';

    public function isActive(): bool
    {
        return $this === self::Connected;
    }

    public function canSync(): bool
    {
        return $this === self::Connected;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Connected => in_array($target, [self::Connected, self::TokenExpired, self::Revoked, self::Error], true),
            self::TokenExpired => in_array($target, [self::Connected, self::Revoked, self::Error], true),
            self::Error => in_array($target, [self::Connected, self::Revoked], true),
            self::Revoked => false,
        };
    }
}
