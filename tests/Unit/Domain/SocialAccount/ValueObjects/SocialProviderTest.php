<?php

declare(strict_types=1);

use App\Domain\SocialAccount\ValueObjects\SocialProvider;

it('creates instagram provider', function () {
    $provider = SocialProvider::Instagram;

    expect($provider->value)->toBe('instagram')
        ->and($provider->label())->toBe('Instagram')
        ->and($provider->supportsReels())->toBeTrue()
        ->and($provider->supportsStories())->toBeTrue()
        ->and($provider->supportsShorts())->toBeFalse();
});

it('creates tiktok provider', function () {
    $provider = SocialProvider::TikTok;

    expect($provider->value)->toBe('tiktok')
        ->and($provider->label())->toBe('TikTok')
        ->and($provider->supportsReels())->toBeFalse()
        ->and($provider->supportsStories())->toBeFalse()
        ->and($provider->supportsShorts())->toBeTrue();
});

it('creates youtube provider', function () {
    $provider = SocialProvider::YouTube;

    expect($provider->value)->toBe('youtube')
        ->and($provider->label())->toBe('YouTube')
        ->and($provider->supportsReels())->toBeFalse()
        ->and($provider->supportsStories())->toBeFalse()
        ->and($provider->supportsShorts())->toBeTrue();
});

it('creates from string value', function () {
    $provider = SocialProvider::from('instagram');

    expect($provider)->toBe(SocialProvider::Instagram);
});

it('rejects invalid provider', function () {
    SocialProvider::from('facebook');
})->throws(ValueError::class);
