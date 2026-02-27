<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

enum AdAccountStatus: string
{
    case Active = 'active';
    case TokenExpired = 'token_expired';
    case Suspended = 'suspended';
    case Disconnected = 'disconnected';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::TokenExpired => 'Token Expirado',
            self::Suspended => 'Suspensa',
            self::Disconnected => 'Desconectada',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Active => in_array($target, [self::TokenExpired, self::Suspended, self::Disconnected], true),
            self::TokenExpired => in_array($target, [self::Active, self::Disconnected], true),
            self::Suspended => in_array($target, [self::Active, self::Disconnected], true),
            self::Disconnected => false,
        };
    }

    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
