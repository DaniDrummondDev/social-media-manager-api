<?php

declare(strict_types=1);

use App\Application\Media\DTOs\ScanMediaInput;
use App\Application\Media\Exceptions\MediaNotFoundException;
use App\Application\Media\UseCases\ScanMediaUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Media\Entities\Media;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->mediaRepository = Mockery::mock(MediaRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new ScanMediaUseCase(
        $this->mediaRepository,
        $this->eventDispatcher,
    );
});

it('marks media as clean', function () {
    $media = Media::create(
        organizationId: Uuid::generate(),
        uploadedBy: Uuid::generate(),
        fileName: 'abc.jpg',
        originalName: 'photo.jpg',
        mimeType: MimeType::fromString('image/jpeg'),
        fileSize: FileSize::fromBytes(1024),
        storagePath: 'path',
        disk: 'spaces',
        checksum: 'hash',
    );

    $this->mediaRepository->shouldReceive('findById')->once()->andReturn($media);
    $this->mediaRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $this->useCase->execute(new ScanMediaInput(
        mediaId: (string) $media->id,
        scanResult: 'clean',
    ));
});

it('marks media as rejected', function () {
    $media = Media::create(
        organizationId: Uuid::generate(),
        uploadedBy: Uuid::generate(),
        fileName: 'abc.jpg',
        originalName: 'photo.jpg',
        mimeType: MimeType::fromString('image/jpeg'),
        fileSize: FileSize::fromBytes(1024),
        storagePath: 'path',
        disk: 'spaces',
        checksum: 'hash',
    );

    $this->mediaRepository->shouldReceive('findById')->once()->andReturn($media);
    $this->mediaRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $this->useCase->execute(new ScanMediaInput(
        mediaId: (string) $media->id,
        scanResult: 'rejected',
    ));
});

it('throws when media not found', function () {
    $this->mediaRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new ScanMediaInput(
        mediaId: (string) Uuid::generate(),
        scanResult: 'clean',
    ));
})->throws(MediaNotFoundException::class);
