<?php

declare(strict_types=1);

use App\Domain\Media\Exceptions\FileSizeExceededException;
use App\Domain\Media\ValueObjects\FileSize;

it('creates from bytes', function () {
    $size = FileSize::fromBytes(1024);

    expect($size->bytes)->toBe(1024)
        ->and($size->toKilobytes())->toBe(1.0)
        ->and((string) $size)->toBe('1024');
});

it('converts to megabytes and gigabytes', function () {
    $size = FileSize::fromBytes(10 * 1024 * 1024); // 10MB

    expect($size->toMegabytes())->toBe(10.0)
        ->and($size->toGigabytes())->toBeGreaterThan(0.009);
});

it('checks size limits', function () {
    $size = FileSize::fromBytes(15 * 1024 * 1024); // 15MB
    $maxSimple = FileSize::MAX_SIMPLE_UPLOAD;       // 10MB

    expect($size->exceedsLimit($maxSimple))->toBeTrue()
        ->and($size->requiresChunkedUpload())->toBeTrue();

    $small = FileSize::fromBytes(5 * 1024 * 1024); // 5MB

    expect($small->exceedsLimit($maxSimple))->toBeFalse()
        ->and($small->requiresChunkedUpload())->toBeFalse();
});

it('rejects zero or negative size', function () {
    FileSize::fromBytes(0);
})->throws(FileSizeExceededException::class);
