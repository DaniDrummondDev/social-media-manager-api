<?php

declare(strict_types=1);

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\ChunkReceivedOutput;
use App\Application\Media\DTOs\UploadChunkInput;
use App\Application\Media\Exceptions\UploadNotFoundException;
use App\Application\Media\UseCases\UploadChunkUseCase;
use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->uploadRepository = Mockery::mock(MediaUploadRepositoryInterface::class);
    $this->chunkedStorage = Mockery::mock(ChunkedStorageInterface::class);
    $this->mediaStorage = Mockery::mock(MediaStorageInterface::class);

    $this->useCase = new UploadChunkUseCase(
        $this->uploadRepository,
        $this->chunkedStorage,
        $this->mediaStorage,
    );
});

it('receives chunk successfully', function () {
    $orgId = Uuid::generate();
    $upload = MediaUpload::create(
        organizationId: $orgId,
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 10 * 1024 * 1024,
        chunkSizeBytes: 5 * 1024 * 1024,
    )->setS3UploadId('s3-upload-id');

    $this->uploadRepository->shouldReceive('findById')->once()->andReturn($upload);
    $this->mediaStorage->shouldReceive('generatePath')->once()->andReturn('orgs/org/media/file.mp4');
    $this->chunkedStorage->shouldReceive('uploadPart')->once()->andReturn('etag-1');
    $this->uploadRepository->shouldReceive('update')->once();

    $output = $this->useCase->execute(new UploadChunkInput(
        organizationId: (string) $orgId,
        uploadId: (string) $upload->id,
        chunkIndex: 1,
        data: 'chunk-data',
    ));

    expect($output)->toBeInstanceOf(ChunkReceivedOutput::class)
        ->and($output->chunkIndex)->toBe(1)
        ->and($output->receivedCount)->toBe(1)
        ->and($output->totalChunks)->toBe(2)
        ->and($output->allChunksReceived)->toBeFalse();
});

it('throws when upload not found', function () {
    $this->uploadRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new UploadChunkInput(
        organizationId: (string) Uuid::generate(),
        uploadId: (string) Uuid::generate(),
        chunkIndex: 1,
        data: 'data',
    ));
})->throws(UploadNotFoundException::class);

it('throws when upload belongs to different organization', function () {
    $upload = MediaUpload::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 10 * 1024 * 1024,
    );

    $this->uploadRepository->shouldReceive('findById')->once()->andReturn($upload);

    $this->useCase->execute(new UploadChunkInput(
        organizationId: (string) Uuid::generate(), // different org
        uploadId: (string) $upload->id,
        chunkIndex: 1,
        data: 'data',
    ));
})->throws(UploadNotFoundException::class);
