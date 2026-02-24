<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\CompleteUploadInput;
use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\Exceptions\UploadNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Media\Entities\Media;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Shared\ValueObjects\Uuid;

final class CompleteUploadUseCase
{
    public function __construct(
        private readonly MediaUploadRepositoryInterface $uploadRepository,
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly ChunkedStorageInterface $chunkedStorage,
        private readonly MediaStorageInterface $mediaStorage,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CompleteUploadInput $input): MediaOutput
    {
        $upload = $this->uploadRepository->findById(Uuid::fromString($input->uploadId));

        if ($upload === null || (string) $upload->organizationId !== $input->organizationId) {
            throw new UploadNotFoundException($input->uploadId);
        }

        $completing = $upload->complete($input->checksum);

        $generatedName = (string) $upload->id.'.'.$upload->mimeType->extension();
        $key = $this->mediaStorage->generatePath($input->organizationId, $generatedName);

        $storagePath = $this->chunkedStorage->complete(
            s3UploadId: $upload->s3UploadId,
            key: $key,
            parts: $completing->s3Parts,
        );

        $completed = $completing->markCompleted();
        $this->uploadRepository->update($completed);

        $media = Media::create(
            organizationId: Uuid::fromString($input->organizationId),
            uploadedBy: Uuid::fromString($input->userId),
            fileName: $generatedName,
            originalName: $upload->fileName,
            mimeType: $upload->mimeType,
            fileSize: FileSize::fromBytes($upload->totalBytes),
            storagePath: $storagePath,
            disk: 'spaces',
            checksum: $input->checksum,
        );

        $this->mediaRepository->create($media);
        $this->eventDispatcher->dispatch(...$media->domainEvents);

        return MediaOutput::fromEntity($media);
    }
}
