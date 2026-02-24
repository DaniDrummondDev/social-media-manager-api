<?php

declare(strict_types=1);

use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\Exceptions\MediaNotFoundException;
use App\Application\Media\UseCases\CalculateCompatibilityUseCase;
use App\Domain\Media\Entities\Media;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\Dimensions;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->mediaRepository = Mockery::mock(MediaRepositoryInterface::class);

    $this->useCase = new CalculateCompatibilityUseCase($this->mediaRepository);
});

it('recalculates compatibility', function () {
    $media = Media::create(
        organizationId: Uuid::generate(),
        uploadedBy: Uuid::generate(),
        fileName: 'abc.jpg',
        originalName: 'photo.jpg',
        mimeType: MimeType::fromString('image/jpeg'),
        fileSize: FileSize::fromBytes(2 * 1024 * 1024),
        storagePath: 'path',
        disk: 'spaces',
        checksum: 'hash',
        dimensions: Dimensions::create(1080, 1080),
    );

    $this->mediaRepository->shouldReceive('findById')->once()->andReturn($media);
    $this->mediaRepository->shouldReceive('update')->once();

    $output = $this->useCase->execute((string) $media->id);

    expect($output)->toBeInstanceOf(MediaOutput::class)
        ->and($output->compatibility)->not->toBeNull();
});

it('throws when media not found', function () {
    $this->mediaRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute((string) Uuid::generate());
})->throws(MediaNotFoundException::class);
