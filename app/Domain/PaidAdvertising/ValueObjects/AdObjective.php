<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

enum AdObjective: string
{
    case Reach = 'reach';
    case Engagement = 'engagement';
    case Traffic = 'traffic';
    case Conversions = 'conversions';

    public function label(): string
    {
        return match ($this) {
            self::Reach => 'Alcance',
            self::Engagement => 'Engajamento',
            self::Traffic => 'Trafego',
            self::Conversions => 'Conversoes',
        };
    }

    /**
     * @return array<string, string>
     */
    public function platformMapping(): array
    {
        return match ($this) {
            self::Reach => ['meta' => 'OUTCOME_AWARENESS', 'tiktok' => 'REACH', 'google' => 'DISPLAY_REACH'],
            self::Engagement => ['meta' => 'OUTCOME_ENGAGEMENT', 'tiktok' => 'ENGAGEMENT', 'google' => 'VIDEO_VIEWS'],
            self::Traffic => ['meta' => 'OUTCOME_TRAFFIC', 'tiktok' => 'TRAFFIC', 'google' => 'WEBSITE_TRAFFIC'],
            self::Conversions => ['meta' => 'OUTCOME_SALES', 'tiktok' => 'CONVERSIONS', 'google' => 'SALES'],
        };
    }
}
