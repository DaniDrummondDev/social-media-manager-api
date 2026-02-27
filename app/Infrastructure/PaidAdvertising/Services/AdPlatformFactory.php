<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Services;

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;

final class AdPlatformFactory implements AdPlatformFactoryInterface
{
    public function make(AdProvider $provider): AdPlatformInterface
    {
        return match ($provider) {
            AdProvider::Meta => new StubMetaAdPlatform(
                config: config('ads.meta', []),
            ),
            AdProvider::TikTok => new StubTikTokAdPlatform(
                config: config('ads.tiktok', []),
            ),
            AdProvider::Google => new StubGoogleAdPlatform(
                config: config('ads.google', []),
            ),
        };
    }
}
