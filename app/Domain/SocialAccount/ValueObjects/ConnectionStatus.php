<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\ValueObjects;

enum ConnectionStatus: string
{
    case Connected = 'connected';
    case TokenExpired = 'token_expired';
    case RequiresReconnection = 'requires_reconnection';
    case Disconnected = 'disconnected';

    public function isActive(): bool
    {
        return $this === self::Connected;
    }

    public function canRefreshToken(): bool
    {
        return in_array($this, [self::Connected, self::TokenExpired], true);
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Connected => in_array($target, [self::TokenExpired, self::Disconnected], true),
            self::TokenExpired => in_array($target, [self::Connected, self::RequiresReconnection, self::Disconnected], true),
            self::RequiresReconnection => in_array($target, [self::Connected, self::Disconnected], true),
            self::Disconnected => false,
        };
    }
}
