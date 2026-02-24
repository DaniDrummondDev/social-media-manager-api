<?php

declare(strict_types=1);

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\InitiateUploadInput;
use App\Application\Media\DTOs\InitiateUploadOutput;
use App\Application\Media\UseCases\InitiateUploadUseCase;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->uploadRepository = Mockery::mock(MediaUploadRepositoryInterface::class);
    $this->chunkedStorage = Mockery::mock(ChunkedStorageInterface::class);
    $this->mediaStorage = Mockery::mock(MediaStorageInterface::class);

    $this->useCase = new InitiateUploadUseCase(
        $this->uploadRepository,
        $this->chunkedStorage,
        $this->mediaStorage,
    );
});

it('initiates chunked upload session', function () {
    $this->mediaStorage->shouldReceive('generatePath')->once()->andReturn('orgs/org-1/media/file.mp4');
    $this->chunkedStorage->shouldReceive('initiate')->once()->andReturn('s3-upload-id-123');
    $this->uploadRepository->shouldReceive('create')->once();

    $output = $this->useCase->execute(new InitiateUploadInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: 'video/mp4',
        totalBytes: 25 * 1024 * 1024,
        chunkSizeBytes: 5 * 1024 * 1024,
    ));

    expect($output)->toBeInstanceOf(InitiateUploadOutput::class)
        ->and($output->s3UploadId)->toBe('s3-upload-id-123')
        ->and($output->totalChunks)->toBe(5)
        ->and($output->chunkSizeBytes)->toBe(5 * 1024 * 1024);
});

it('uses default chunk size when not specified', function () {
    $this->mediaStorage->shouldReceive('generatePath')->once()->andReturn('orgs/org-1/media/file.mp4');
    $this->chunkedStorage->shouldReceive('initiate')->once()->andReturn('s3-id');
    $this->uploadRepository->shouldReceive('create')->once();

    $output = $this->useCase->execute(new InitiateUploadInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: 'video/mp4',
        totalBytes: 25 * 1024 * 1024,
    ));

    expect($output->chunkSizeBytes)->toBe(5 * 1024 * 1024);
});
