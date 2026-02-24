<?php

declare(strict_types=1);

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\AbortUploadInput;
use App\Application\Media\Exceptions\UploadNotFoundException;
use App\Application\Media\UseCases\AbortUploadUseCase;
use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->uploadRepository = Mockery::mock(MediaUploadRepositoryInterface::class);
    $this->chunkedStorage = Mockery::mock(ChunkedStorageInterface::class);
    $this->mediaStorage = Mockery::mock(MediaStorageInterface::class);

    $this->useCase = new AbortUploadUseCase(
        $this->uploadRepository,
        $this->chunkedStorage,
        $this->mediaStorage,
    );
});

it('aborts upload successfully', function () {
    $orgId = Uuid::generate();
    $upload = MediaUpload::create(
        organizationId: $orgId,
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 25 * 1024 * 1024,
    )->setS3UploadId('s3-upload-id');

    $this->uploadRepository->shouldReceive('findById')->once()->andReturn($upload);
    $this->uploadRepository->shouldReceive('update')->once();
    $this->mediaStorage->shouldReceive('generatePath')->once()->andReturn('orgs/org/media/file.mp4');
    $this->chunkedStorage->shouldReceive('abort')->once();

    $this->useCase->execute(new AbortUploadInput(
        organizationId: (string) $orgId,
        uploadId: (string) $upload->id,
    ));
});

it('throws when upload not found', function () {
    $this->uploadRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new AbortUploadInput(
        organizationId: (string) Uuid::generate(),
        uploadId: (string) Uuid::generate(),
    ));
})->throws(UploadNotFoundException::class);
