<?php

declare(strict_types=1);

use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Media\ValueObjects\UploadStatus;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

function createUpload(int $totalBytes = 25 * 1024 * 1024, int $chunkSize = 5 * 1024 * 1024): MediaUpload
{
    return MediaUpload::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: $totalBytes,
        chunkSizeBytes: $chunkSize,
    );
}

it('creates upload session and calculates total chunks', function () {
    $upload = createUpload(25 * 1024 * 1024, 5 * 1024 * 1024); // 25MB / 5MB = 5 chunks

    expect($upload->totalChunks)->toBe(5)
        ->and($upload->status)->toBe(UploadStatus::Initiated)
        ->and($upload->receivedChunks)->toBeEmpty()
        ->and($upload->s3Parts)->toBeEmpty()
        ->and($upload->checksum)->toBeNull()
        ->and($upload->progress())->toBe(0.0);
});

it('calculates total chunks with remainder', function () {
    $upload = createUpload(12 * 1024 * 1024, 5 * 1024 * 1024); // 12MB / 5MB = 3 chunks (ceil)

    expect($upload->totalChunks)->toBe(3);
});

it('receives chunks out of order', function () {
    $upload = createUpload();

    $upload = $upload->receiveChunk(3, 'etag-3');
    $upload = $upload->receiveChunk(1, 'etag-1');

    expect($upload->receivedChunks)->toBe([3, 1])
        ->and($upload->s3Parts)->toBe([3 => 'etag-3', 1 => 'etag-1'])
        ->and($upload->status)->toBe(UploadStatus::Uploading)
        ->and($upload->progress())->toBe(40.0); // 2/5
});

it('rejects duplicate chunk', function () {
    $upload = createUpload();
    $upload = $upload->receiveChunk(1, 'etag-1');
    $upload->receiveChunk(1, 'etag-1-again');
})->throws(DomainException::class, 'already been received');

it('rejects invalid chunk index', function () {
    $upload = createUpload(); // 5 chunks
    $upload->receiveChunk(6, 'etag-6');
})->throws(DomainException::class, 'Invalid chunk index');

it('rejects chunk index zero', function () {
    $upload = createUpload();
    $upload->receiveChunk(0, 'etag-0');
})->throws(DomainException::class, 'Invalid chunk index');

it('completes when all chunks received', function () {
    $upload = createUpload(10 * 1024 * 1024, 5 * 1024 * 1024); // 2 chunks

    $upload = $upload->receiveChunk(1, 'etag-1');
    $upload = $upload->receiveChunk(2, 'etag-2');
    $completing = $upload->complete('sha256-checksum');

    expect($completing->status)->toBe(UploadStatus::Completing)
        ->and($completing->checksum)->toBe('sha256-checksum')
        ->and($completing->allChunksReceived())->toBeTrue();

    $completed = $completing->markCompleted();

    expect($completed->status)->toBe(UploadStatus::Completed);
});

it('rejects complete when chunks missing', function () {
    $upload = createUpload(10 * 1024 * 1024, 5 * 1024 * 1024); // 2 chunks
    $upload = $upload->receiveChunk(1, 'etag-1');
    $upload->complete('checksum');
})->throws(DomainException::class, '1/2 chunks received');

it('aborts upload', function () {
    $upload = createUpload();
    $upload = $upload->receiveChunk(1, 'etag-1');
    $aborted = $upload->abort();

    expect($aborted->status)->toBe(UploadStatus::Aborted)
        ->and($aborted->status->isTerminal())->toBeTrue();
});

it('expires upload', function () {
    $upload = createUpload();
    $expired = $upload->expire();

    expect($expired->status)->toBe(UploadStatus::Expired)
        ->and($expired->status->isTerminal())->toBeTrue();
});

it('rejects operations on terminal upload', function () {
    $upload = createUpload();
    $aborted = $upload->abort();
    $aborted->receiveChunk(1, 'etag');
})->throws(DomainException::class, 'no longer active');

it('rejects abort on terminal upload', function () {
    $upload = createUpload();
    $aborted = $upload->abort();
    $aborted->abort();
})->throws(DomainException::class, 'terminal');

it('rejects invalid chunk size too small', function () {
    MediaUpload::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 10 * 1024 * 1024,
        chunkSizeBytes: 512 * 1024, // 512KB < 1MB minimum
    );
})->throws(DomainException::class, 'between 1MB and 10MB');

it('rejects invalid chunk size too large', function () {
    MediaUpload::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 100 * 1024 * 1024,
        chunkSizeBytes: 20 * 1024 * 1024, // 20MB > 10MB maximum
    );
})->throws(DomainException::class, 'between 1MB and 10MB');

it('releases events', function () {
    $upload = createUpload();

    $released = $upload->releaseEvents();

    expect($released->domainEvents)->toBeEmpty();
});

it('progress reaches 100 when complete', function () {
    $upload = createUpload(10 * 1024 * 1024, 5 * 1024 * 1024); // 2 chunks

    $upload = $upload->receiveChunk(1, 'etag-1');

    expect($upload->progress())->toBe(50.0);

    $upload = $upload->receiveChunk(2, 'etag-2');

    expect($upload->progress())->toBe(100.0);
});
