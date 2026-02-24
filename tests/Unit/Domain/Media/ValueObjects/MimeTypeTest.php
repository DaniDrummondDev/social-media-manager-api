<?php

declare(strict_types=1);

use App\Domain\Media\Exceptions\InvalidMimeTypeException;
use App\Domain\Media\ValueObjects\MediaType;
use App\Domain\Media\ValueObjects\MimeType;

it('creates valid image mime types', function (string $mime, string $ext) {
    $mimeType = MimeType::fromString($mime);

    expect($mimeType->value)->toBe($mime)
        ->and($mimeType->isImage())->toBeTrue()
        ->and($mimeType->isVideo())->toBeFalse()
        ->and($mimeType->mediaType())->toBe(MediaType::Image)
        ->and($mimeType->extension())->toBe($ext);
})->with([
    ['image/jpeg', 'jpg'],
    ['image/png', 'png'],
    ['image/webp', 'webp'],
    ['image/gif', 'gif'],
]);

it('creates valid video mime types', function (string $mime, string $ext) {
    $mimeType = MimeType::fromString($mime);

    expect($mimeType->value)->toBe($mime)
        ->and($mimeType->isVideo())->toBeTrue()
        ->and($mimeType->isImage())->toBeFalse()
        ->and($mimeType->mediaType())->toBe(MediaType::Video)
        ->and($mimeType->extension())->toBe($ext);
})->with([
    ['video/mp4', 'mp4'],
    ['video/quicktime', 'mov'],
    ['video/webm', 'webm'],
]);

it('rejects unsupported mime type', function () {
    MimeType::fromString('application/pdf');
})->throws(InvalidMimeTypeException::class);

it('normalizes case', function () {
    $mimeType = MimeType::fromString('IMAGE/JPEG');

    expect($mimeType->value)->toBe('image/jpeg');
});

it('compares equality', function () {
    $a = MimeType::fromString('image/png');
    $b = MimeType::fromString('image/png');
    $c = MimeType::fromString('video/mp4');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
