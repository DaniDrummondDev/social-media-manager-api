<?php

declare(strict_types=1);

use App\Application\Publishing\Contracts\SocialPublisherFactoryInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Publishing\Adapters\InstagramPublisher;
use App\Infrastructure\Publishing\Adapters\TikTokPublisher;
use App\Infrastructure\Publishing\Adapters\YouTubePublisher;

beforeEach(function () {
    $this->factory = app(SocialPublisherFactoryInterface::class);
});

it('resolves InstagramPublisher for instagram provider', function () {
    $publisher = $this->factory->make(SocialProvider::Instagram);

    expect($publisher)->toBeInstanceOf(InstagramPublisher::class);
});

it('resolves TikTokPublisher for tiktok provider', function () {
    $publisher = $this->factory->make(SocialProvider::TikTok);

    expect($publisher)->toBeInstanceOf(TikTokPublisher::class);
});

it('resolves YouTubePublisher for youtube provider', function () {
    $publisher = $this->factory->make(SocialProvider::YouTube);

    expect($publisher)->toBeInstanceOf(YouTubePublisher::class);
});

it('stub adapters throw RuntimeException on publish', function (SocialProvider $provider) {
    $publisher = $this->factory->make($provider);

    expect(fn () => $publisher->publish([]))->toThrow(RuntimeException::class);
})->with([
    'instagram' => [SocialProvider::Instagram],
    'tiktok' => [SocialProvider::TikTok],
    'youtube' => [SocialProvider::YouTube],
]);

it('stub adapters throw RuntimeException on getPostStatus', function (SocialProvider $provider) {
    $publisher = $this->factory->make($provider);

    expect(fn () => $publisher->getPostStatus('ext-123'))->toThrow(RuntimeException::class);
})->with([
    'instagram' => [SocialProvider::Instagram],
    'tiktok' => [SocialProvider::TikTok],
    'youtube' => [SocialProvider::YouTube],
]);

it('stub adapters throw RuntimeException on deletePost', function (SocialProvider $provider) {
    $publisher = $this->factory->make($provider);

    expect(fn () => $publisher->deletePost('ext-123'))->toThrow(RuntimeException::class);
})->with([
    'instagram' => [SocialProvider::Instagram],
    'tiktok' => [SocialProvider::TikTok],
    'youtube' => [SocialProvider::YouTube],
]);
