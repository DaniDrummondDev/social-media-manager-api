<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Services;

use App\Application\Publishing\Contracts\SocialPublisherFactoryInterface;
use App\Domain\SocialAccount\Contracts\SocialPublisherInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Publishing\Adapters\InstagramPublisher;
use App\Infrastructure\Publishing\Adapters\TikTokPublisher;
use App\Infrastructure\Publishing\Adapters\YouTubePublisher;

final class SocialPublisherFactory implements SocialPublisherFactoryInterface
{
    public function make(SocialProvider $provider): SocialPublisherInterface
    {
        return match ($provider) {
            SocialProvider::Instagram => new InstagramPublisher,
            SocialProvider::TikTok => new TikTokPublisher,
            SocialProvider::YouTube => new YouTubePublisher,
        };
    }
}
