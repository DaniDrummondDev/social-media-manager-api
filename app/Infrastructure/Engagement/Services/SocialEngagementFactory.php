<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Application\Engagement\Contracts\SocialEngagementFactoryInterface;
use App\Domain\SocialAccount\Contracts\SocialEngagementInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Engagement\Adapters\InstagramEngagement;
use App\Infrastructure\Engagement\Adapters\TikTokEngagement;
use App\Infrastructure\Engagement\Adapters\YouTubeEngagement;

final class SocialEngagementFactory implements SocialEngagementFactoryInterface
{
    public function make(SocialProvider $provider): SocialEngagementInterface
    {
        return match ($provider) {
            SocialProvider::Instagram => new InstagramEngagement,
            SocialProvider::TikTok => new TikTokEngagement,
            SocialProvider::YouTube => new YouTubeEngagement,
        };
    }
}
