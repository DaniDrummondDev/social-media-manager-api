<?php

declare(strict_types=1);

use App\Domain\Media\Entities\Media;
use App\Domain\Media\Events\MediaDeleted;
use App\Domain\Media\Events\MediaRestored;
use App\Domain\Media\Events\MediaScanned;
use App\Domain\Media\Events\MediaUploaded;
use App\Domain\Media\Exceptions\MediaNotUsableException;
use App\Domain\Media\ValueObjects\Dimensions;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Media\ValueObjects\ScanStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createMedia(?Dimensions $dimensions = null, ?int $duration = null): Media
{
    return Media::create(
        organizationId: Uuid::generate(),
        uploadedBy: Uuid::generate(),
        fileName: 'abc123.jpg',
        originalName: 'photo.jpg',
        mimeType: MimeType::fromString('image/jpeg'),
        fileSize: FileSize::fromBytes(2 * 1024 * 1024),
        storagePath: 'orgs/org-1/media/abc123.jpg',
        disk: 'spaces',
        checksum: hash('sha256', 'test'),
        dimensions: $dimensions ?? Dimensions::create(1080, 1080),
        durationSeconds: $duration,
    );
}

it('creates media with uploaded event', function () {
    $media = createMedia();

    expect($media->scanStatus)->toBe(ScanStatus::Pending)
        ->and($media->thumbnailPath)->toBeNull()
        ->and($media->deletedAt)->toBeNull()
        ->and($media->purgeAt)->toBeNull()
        ->and($media->compatibility)->not->toBeNull()
        ->and($media->domainEvents)->toHaveCount(1)
        ->and($media->domainEvents[0])->toBeInstanceOf(MediaUploaded::class)
        ->and($media->domainEvents[0]->fileName)->toBe('abc123.jpg');
});

it('reconstitutes without events', function () {
    $media = Media::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        uploadedBy: Uuid::generate(),
        fileName: 'abc.jpg',
        originalName: 'photo.jpg',
        mimeType: MimeType::fromString('image/jpeg'),
        fileSize: FileSize::fromBytes(1024),
        dimensions: null,
        durationSeconds: null,
        storagePath: 'path',
        thumbnailPath: null,
        disk: 'spaces',
        checksum: 'hash',
        scanStatus: ScanStatus::Clean,
        scannedAt: new DateTimeImmutable,
        compatibility: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
    );

    expect($media->domainEvents)->toBeEmpty();
});

it('marks as clean and emits scanned event', function () {
    $media = createMedia();
    $clean = $media->markAsClean();

    expect($clean->scanStatus)->toBe(ScanStatus::Clean)
        ->and($clean->scannedAt)->not->toBeNull()
        ->and($clean->domainEvents)->toHaveCount(2)
        ->and($clean->domainEvents[1])->toBeInstanceOf(MediaScanned::class)
        ->and($clean->domainEvents[1]->scanResult)->toBe('clean');
});

it('marks as rejected and emits scanned event', function () {
    $media = createMedia();
    $rejected = $media->markAsRejected();

    expect($rejected->scanStatus)->toBe(ScanStatus::Rejected)
        ->and($rejected->scannedAt)->not->toBeNull()
        ->and($rejected->domainEvents)->toHaveCount(2)
        ->and($rejected->domainEvents[1])->toBeInstanceOf(MediaScanned::class)
        ->and($rejected->domainEvents[1]->scanResult)->toBe('rejected');
});

it('throws when scanning non-pending media', function () {
    $media = createMedia();
    $clean = $media->markAsClean();
    $clean->markAsClean();
})->throws(MediaNotUsableException::class, 'scan has already been processed');

it('soft deletes with grace period and emits event', function () {
    $media = createMedia();
    $deleted = $media->softDelete(30);

    expect($deleted->deletedAt)->not->toBeNull()
        ->and($deleted->purgeAt)->not->toBeNull()
        ->and($deleted->isDeleted())->toBeTrue()
        ->and($deleted->isUsable())->toBeFalse()
        ->and($deleted->domainEvents)->toHaveCount(2)
        ->and($deleted->domainEvents[1])->toBeInstanceOf(MediaDeleted::class);
});

it('throws when soft deleting already deleted media', function () {
    $media = createMedia();
    $deleted = $media->softDelete();
    $deleted->softDelete();
})->throws(MediaNotUsableException::class, 'deleted');

it('restores deleted media and emits event', function () {
    $media = createMedia();
    $deleted = $media->softDelete();
    $restored = $deleted->restore();

    expect($restored->deletedAt)->toBeNull()
        ->and($restored->purgeAt)->toBeNull()
        ->and($restored->domainEvents)->toHaveCount(3)
        ->and($restored->domainEvents[2])->toBeInstanceOf(MediaRestored::class);
});

it('returns same instance when restoring non-deleted media', function () {
    $media = createMedia();
    $result = $media->restore();

    expect($result)->toBe($media);
});

it('reports usable status correctly', function () {
    $media = createMedia();

    expect($media->isUsable())->toBeFalse(); // pending scan

    $clean = $media->markAsClean();

    expect($clean->isUsable())->toBeTrue();

    $deleted = $clean->softDelete();

    expect($deleted->isUsable())->toBeFalse();
});

it('sets thumbnail path', function () {
    $media = createMedia();
    $withThumb = $media->setThumbnailPath('thumbs/abc.jpg');

    expect($withThumb->thumbnailPath)->toBe('thumbs/abc.jpg');
});

it('releases events', function () {
    $media = createMedia();

    expect($media->domainEvents)->toHaveCount(1);

    $released = $media->releaseEvents();

    expect($released->domainEvents)->toBeEmpty();
});
