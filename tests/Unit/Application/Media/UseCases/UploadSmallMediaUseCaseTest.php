<?php

declare(strict_types=1);

use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\DTOs\UploadSmallMediaInput;
use App\Application\Media\UseCases\UploadSmallMediaUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Media\Exceptions\InvalidMimeTypeException;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->mediaRepository = Mockery::mock(MediaRepositoryInterface::class);
    $this->mediaStorage = Mockery::mock(MediaStorageInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new UploadSmallMediaUseCase(
        $this->mediaRepository,
        $this->mediaStorage,
        $this->eventDispatcher,
    );
});

it('uploads small media successfully', function () {
    $this->mediaStorage->shouldReceive('generatePath')->once()->andReturn('orgs/org-1/media/file.jpg');
    $this->mediaStorage->shouldReceive('store')->once();
    $this->mediaRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new UploadSmallMediaInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        originalName: 'photo.jpg',
        mimeType: 'image/jpeg',
        fileSize: 2 * 1024 * 1024,
        contents: 'binary-data',
        checksum: hash('sha256', 'binary-data'),
    ));

    expect($output)->toBeInstanceOf(MediaOutput::class)
        ->and($output->originalName)->toBe('photo.jpg')
        ->and($output->mimeType)->toBe('image/jpeg')
        ->and($output->scanStatus)->toBe('pending');
});

it('throws on invalid mime type', function () {
    $this->useCase->execute(new UploadSmallMediaInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        originalName: 'doc.pdf',
        mimeType: 'application/pdf',
        fileSize: 1024,
        contents: 'data',
        checksum: 'hash',
    ));
})->throws(InvalidMimeTypeException::class);
