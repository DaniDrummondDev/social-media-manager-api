<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\ValueObjects;

enum SocialProvider: string
{
    case Instagram = 'instagram';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::TikTok => 'TikTok',
            self::YouTube => 'YouTube',
        };
    }

    public function supportsReels(): bool
    {
        return $this === self::Instagram;
    }

    public function supportsStories(): bool
    {
        return $this === self::Instagram;
    }

    public function supportsShorts(): bool
    {
        return match ($this) {
            self::YouTube => true,
            self::TikTok => true,
            default => false,
        };
    }
}
