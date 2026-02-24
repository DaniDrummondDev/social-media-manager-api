<?php

declare(strict_types=1);

use App\Application\Media\DTOs\GetUploadStatusInput;
use App\Application\Media\DTOs\UploadStatusOutput;
use App\Application\Media\Exceptions\UploadNotFoundException;
use App\Application\Media\UseCases\GetUploadStatusUseCase;
use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->uploadRepository = Mockery::mock(MediaUploadRepositoryInterface::class);

    $this->useCase = new GetUploadStatusUseCase($this->uploadRepository);
});

it('returns upload status', function () {
    $orgId = Uuid::generate();
    $upload = MediaUpload::create(
        organizationId: $orgId,
        userId: Uuid::generate(),
        fileName: 'video.mp4',
        mimeType: MimeType::fromString('video/mp4'),
        totalBytes: 15 * 1024 * 1024,
        chunkSizeBytes: 5 * 1024 * 1024,
    );
    $upload = $upload->receiveChunk(1, 'etag-1');

    $this->uploadRepository->shouldReceive('findById')->once()->andReturn($upload);

    $output = $this->useCase->execute(new GetUploadStatusInput(
        organizationId: (string) $orgId,
        uploadId: (string) $upload->id,
    ));

    expect($output)->toBeInstanceOf(UploadStatusOutput::class)
        ->and($output->status)->toBe('uploading')
        ->and($output->totalChunks)->toBe(3)
        ->and($output->receivedChunks)->toBe([1])
        ->and($output->progress)->toBeGreaterThan(0);
});

it('throws when upload not found', function () {
    $this->uploadRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new GetUploadStatusInput(
        organizationId: (string) Uuid::generate(),
        uploadId: (string) Uuid::generate(),
    ));
})->throws(UploadNotFoundException::class);
