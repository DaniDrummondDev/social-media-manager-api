<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

enum AdProvider: string
{
    case Meta = 'meta';
    case TikTok = 'tiktok';
    case Google = 'google';

    public function label(): string
    {
        return match ($this) {
            self::Meta => 'Meta Ads',
            self::TikTok => 'TikTok Ads',
            self::Google => 'Google Ads',
        };
    }

    public function supportsBoosting(): bool
    {
        return true;
    }

    public function requiresDeveloperToken(): bool
    {
        return $this === self::Google;
    }
}
