<?php

declare(strict_types=1);

use App\Domain\Media\Exceptions\InvalidDimensionsException;
use App\Domain\Media\ValueObjects\Dimensions;

it('creates valid dimensions', function () {
    $dims = Dimensions::create(1920, 1080);

    expect($dims->width)->toBe(1920)
        ->and($dims->height)->toBe(1080);
});

it('rejects zero width', function () {
    Dimensions::create(0, 100);
})->throws(InvalidDimensionsException::class);

it('rejects zero height', function () {
    Dimensions::create(100, 0);
})->throws(InvalidDimensionsException::class);

it('rejects negative dimensions', function () {
    Dimensions::create(-1, -1);
})->throws(InvalidDimensionsException::class);

it('calculates aspect ratio', function () {
    expect(Dimensions::create(1920, 1080)->aspectRatio())->toEqualWithDelta(1.778, 0.001)
        ->and(Dimensions::create(1080, 1920)->aspectRatio())->toEqualWithDelta(0.5625, 0.001);
});

it('detects square', function () {
    expect(Dimensions::create(100, 100)->isSquare())->toBeTrue()
        ->and(Dimensions::create(100, 200)->isSquare())->toBeFalse();
});

it('detects landscape', function () {
    expect(Dimensions::create(1920, 1080)->isLandscape())->toBeTrue()
        ->and(Dimensions::create(1080, 1920)->isLandscape())->toBeFalse();
});

it('detects portrait', function () {
    expect(Dimensions::create(1080, 1920)->isPortrait())->toBeTrue()
        ->and(Dimensions::create(1920, 1080)->isPortrait())->toBeFalse();
});

it('formats as string', function () {
    expect((string) Dimensions::create(1920, 1080))->toBe('1920x1080');
});

it('compares equality', function () {
    $a = Dimensions::create(1920, 1080);
    $b = Dimensions::create(1920, 1080);
    $c = Dimensions::create(1080, 1920);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
