<?php

declare(strict_types=1);

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\CompleteUploadInput;
use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\Exceptions\UploadNotFoundException;
use App\Application\Media\UseCases\CompleteUploadUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->uploadRepository = Mockery::mock(MediaUploadRepositoryInterface::class);
    $this->mediaRepository = Mockery::mock(MediaRepositoryInterface::class);
    $this->chunkedStorage = Mockery::mock(ChunkedStorageInterface::class);
    $this->mediaStorage = Mockery::mock(MediaStorageInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CompleteUploadUseCase(
        $this->uploadRepository,
        $this->mediaRepository,
        $this->chunkedStorage,
        $this->mediaStorage,
        $this->eventDispatcher,
    );
});

it('completes upload and creates media', function () {
    $orgId = Uuid::generate();
    $upload = MediaUpload::create(
        organizationId: $orgId,
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 10 * 1024 * 1024,
        chunkSizeBytes: 5 * 1024 * 1024,
    )->setS3UploadId('s3-id');

    // Receive all chunks
    $upload = $upload->receiveChunk(1, 'etag-1');
    $upload = $upload->receiveChunk(2, 'etag-2');

    $this->uploadRepository->shouldReceive('findById')->once()->andReturn($upload);
    $this->mediaStorage->shouldReceive('generatePath')->once()->andReturn('orgs/org/media/file.mp4');
    $this->chunkedStorage->shouldReceive('complete')->once()->andReturn('orgs/org/media/file.mp4');
    $this->uploadRepository->shouldReceive('update')->once();
    $this->mediaRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new CompleteUploadInput(
        organizationId: (string) $orgId,
        userId: (string) Uuid::generate(),
        uploadId: (string) $upload->id,
        checksum: 'sha256-checksum',
    ));

    expect($output)->toBeInstanceOf(MediaOutput::class)
        ->and($output->mimeType)->toBe('video/mp4')
        ->and($output->scanStatus)->toBe('pending');
});

it('throws when upload has missing chunks', function () {
    $orgId = Uuid::generate();
    $upload = MediaUpload::create(
        organizationId: $orgId,
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 10 * 1024 * 1024,
        chunkSizeBytes: 5 * 1024 * 1024,
    )->setS3UploadId('s3-id');

    $upload = $upload->receiveChunk(1, 'etag-1'); // only 1 of 2 chunks

    $this->uploadRepository->shouldReceive('findById')->once()->andReturn($upload);

    $this->useCase->execute(new CompleteUploadInput(
        organizationId: (string) $orgId,
        userId: (string) Uuid::generate(),
        uploadId: (string) $upload->id,
        checksum: 'checksum',
    ));
})->throws(DomainException::class, 'chunks received');

it('throws when upload not found', function () {
    $this->uploadRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new CompleteUploadInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        uploadId: (string) Uuid::generate(),
        checksum: 'checksum',
    ));
})->throws(UploadNotFoundException::class);
