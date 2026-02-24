<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\DTOs\UploadSmallMediaInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Media\Entities\Media;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

final class UploadSmallMediaUseCase
{
    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly MediaStorageInterface $mediaStorage,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UploadSmallMediaInput $input): MediaOutput
    {
        $mimeType = MimeType::fromString($input->mimeType);
        $fileSize = FileSize::fromBytes($input->fileSize);

        $generatedName = (string) Uuid::generate().'.'.$mimeType->extension();
        $storagePath = $this->mediaStorage->generatePath($input->organizationId, $generatedName);

        $this->mediaStorage->store('spaces', $storagePath, $input->contents);

        $media = Media::create(
            organizationId: Uuid::fromString($input->organizationId),
            uploadedBy: Uuid::fromString($input->userId),
            fileName: $generatedName,
            originalName: $input->originalName,
            mimeType: $mimeType,
            fileSize: $fileSize,
            storagePath: $storagePath,
            disk: 'spaces',
            checksum: $input->checksum,
        );

        $this->mediaRepository->create($media);
        $this->eventDispatcher->dispatch(...$media->domainEvents);

        return MediaOutput::fromEntity($media);
    }
}
