<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Services;

use App\Application\Analytics\Contracts\SocialAnalyticsFactoryInterface;
use App\Domain\SocialAccount\Contracts\SocialAnalyticsInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Analytics\Adapters\InstagramAnalytics;
use App\Infrastructure\Analytics\Adapters\TikTokAnalytics;
use App\Infrastructure\Analytics\Adapters\YouTubeAnalytics;

final class SocialAnalyticsFactory implements SocialAnalyticsFactoryInterface
{
    public function make(SocialProvider $provider): SocialAnalyticsInterface
    {
        return match ($provider) {
            SocialProvider::Instagram => new InstagramAnalytics,
            SocialProvider::TikTok => new TikTokAnalytics,
            SocialProvider::YouTube => new YouTubeAnalytics,
        };
    }
}
