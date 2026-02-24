<?php

declare(strict_types=1);

use App\Application\Media\DTOs\DeleteMediaInput;
use App\Application\Media\Exceptions\MediaNotFoundException;
use App\Application\Media\UseCases\DeleteMediaUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Media\Entities\Media;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\Dimensions;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->mediaRepository = Mockery::mock(MediaRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new DeleteMediaUseCase(
        $this->mediaRepository,
        $this->eventDispatcher,
    );
});

it('soft deletes media successfully', function () {
    $orgId = Uuid::generate();
    $media = Media::create(
        organizationId: $orgId,
        uploadedBy: Uuid::generate(),
        fileName: 'abc.jpg',
        originalName: 'photo.jpg',
        mimeType: MimeType::fromString('image/jpeg'),
        fileSize: FileSize::fromBytes(1024),
        storagePath: 'orgs/org/media/abc.jpg',
        disk: 'spaces',
        checksum: 'hash',
        dimensions: Dimensions::create(800, 600),
    );

    $this->mediaRepository->shouldReceive('findById')->once()->andReturn($media);
    $this->mediaRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $this->useCase->execute(new DeleteMediaInput(
        organizationId: (string) $orgId,
        mediaId: (string) $media->id,
    ));
});

it('throws when media not found', function () {
    $this->mediaRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new DeleteMediaInput(
        organizationId: (string) Uuid::generate(),
        mediaId: (string) Uuid::generate(),
    ));
})->throws(MediaNotFoundException::class);

it('throws when media belongs to different organization', function () {
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

    $this->useCase->execute(new DeleteMediaInput(
        organizationId: (string) Uuid::generate(), // different org
        mediaId: (string) $media->id,
    ));
})->throws(MediaNotFoundException::class);
