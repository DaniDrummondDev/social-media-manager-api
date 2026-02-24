<?php

declare(strict_types=1);

use App\Domain\Media\ValueObjects\Compatibility;
use App\Domain\Media\ValueObjects\Dimensions;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;

it('calculates compatibility for square image', function () {
    $compat = Compatibility::calculate(
        mimeType: MimeType::fromString('image/jpeg'),
        fileSize: FileSize::fromBytes(2 * 1024 * 1024), // 2MB
        dimensions: Dimensions::create(1080, 1080),       // 1:1 square
        durationSeconds: null,
    );

    expect($compat->instagramFeed)->toBeTrue()
        ->and($compat->instagramStory)->toBeFalse()  // not 9:16
        ->and($compat->instagramReel)->toBeFalse()    // image, not video
        ->and($compat->tiktok)->toBeFalse()           // image, not video
        ->and($compat->youtube)->toBeFalse()          // image, not video
        ->and($compat->youtubeShort)->toBeFalse();    // image, not video
});

it('calculates compatibility for vertical video (9:16)', function () {
    $compat = Compatibility::calculate(
        mimeType: MimeType::fromString('video/mp4'),
        fileSize: FileSize::fromBytes(50 * 1024 * 1024), // 50MB
        dimensions: Dimensions::create(1080, 1920),        // 9:16
        durationSeconds: 30,
    );

    expect($compat->instagramFeed)->toBeTrue()    // mp4, ≤100MB, 3–60s
        ->and($compat->instagramStory)->toBeTrue()  // mp4, ≤100MB, ≤60s, 9:16
        ->and($compat->instagramReel)->toBeTrue()   // mp4, ≤100MB, 3–90s, 9:16
        ->and($compat->tiktok)->toBeTrue()           // mp4, ≤287MB, 3–180s
        ->and($compat->youtube)->toBeTrue()          // mp4, ≤128GB
        ->and($compat->youtubeShort)->toBeTrue();    // mp4, ≤60s, 9:16
});

it('rejects video too long for tiktok', function () {
    $compat = Compatibility::calculate(
        mimeType: MimeType::fromString('video/mp4'),
        fileSize: FileSize::fromBytes(50 * 1024 * 1024),
        dimensions: Dimensions::create(1080, 1920),
        durationSeconds: 200, // exceeds TikTok 180s
    );

    expect($compat->tiktok)->toBeFalse()
        ->and($compat->youtubeShort)->toBeFalse()     // exceeds 60s
        ->and($compat->instagramReel)->toBeFalse()    // exceeds 90s
        ->and($compat->instagramStory)->toBeFalse()   // exceeds 60s
        ->and($compat->instagramFeed)->toBeFalse()    // exceeds 60s
        ->and($compat->youtube)->toBeTrue();           // ≤12h
});

it('accepts quicktime for youtube only', function () {
    $compat = Compatibility::calculate(
        mimeType: MimeType::fromString('video/quicktime'),
        fileSize: FileSize::fromBytes(100 * 1024 * 1024),
        dimensions: Dimensions::create(1920, 1080),
        durationSeconds: 600,
    );

    expect($compat->youtube)->toBeTrue()
        ->and($compat->instagramFeed)->toBeFalse()  // not mp4
        ->and($compat->tiktok)->toBeFalse()          // not mp4/webm
        ->and($compat->youtubeShort)->toBeFalse();   // not mp4
});

it('returns all false from none()', function () {
    $compat = Compatibility::none();

    expect($compat->toArray())->toBe([
        'instagram_feed' => false,
        'instagram_story' => false,
        'instagram_reel' => false,
        'tiktok' => false,
        'youtube' => false,
        'youtube_short' => false,
    ]);
});

it('compares equality', function () {
    $a = Compatibility::calculate(
        MimeType::fromString('image/jpeg'),
        FileSize::fromBytes(1024),
        Dimensions::create(1080, 1080),
        null,
    );
    $b = Compatibility::calculate(
        MimeType::fromString('image/jpeg'),
        FileSize::fromBytes(1024),
        Dimensions::create(1080, 1080),
        null,
    );

    expect($a->equals($b))->toBeTrue();
});
