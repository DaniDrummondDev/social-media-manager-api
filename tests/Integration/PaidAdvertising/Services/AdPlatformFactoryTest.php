<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Infrastructure\PaidAdvertising\Services\StubGoogleAdPlatform;
use App\Infrastructure\PaidAdvertising\Services\StubMetaAdPlatform;
use App\Infrastructure\PaidAdvertising\Services\StubTikTokAdPlatform;

beforeEach(function () {
    $this->factory = app(AdPlatformFactoryInterface::class);
});

it('creates meta adapter', function () {
    $adapter = $this->factory->make(AdProvider::Meta);

    expect($adapter)->toBeInstanceOf(StubMetaAdPlatform::class)
        ->and($adapter)->toBeInstanceOf(AdPlatformInterface::class);
});

it('creates tiktok adapter', function () {
    $adapter = $this->factory->make(AdProvider::TikTok);

    expect($adapter)->toBeInstanceOf(StubTikTokAdPlatform::class)
        ->and($adapter)->toBeInstanceOf(AdPlatformInterface::class);
});

it('creates google adapter', function () {
    $adapter = $this->factory->make(AdProvider::Google);

    expect($adapter)->toBeInstanceOf(StubGoogleAdPlatform::class)
        ->and($adapter)->toBeInstanceOf(AdPlatformInterface::class);
});

it('all providers return functional adapters', function () {
    foreach (AdProvider::cases() as $provider) {
        $adapter = $this->factory->make($provider);
        $result = $adapter->connect('https://example.com', 'state-123');

        expect($result)->toHaveKeys(['auth_url', 'state']);
    }
});
