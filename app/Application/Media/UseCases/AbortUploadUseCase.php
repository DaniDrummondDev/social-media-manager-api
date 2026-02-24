<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\AbortUploadInput;
use App\Application\Media\Exceptions\UploadNotFoundException;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class AbortUploadUseCase
{
    public function __construct(
        private readonly MediaUploadRepositoryInterface $uploadRepository,
        private readonly ChunkedStorageInterface $chunkedStorage,
        private readonly MediaStorageInterface $mediaStorage,
    ) {}

    public function execute(AbortUploadInput $input): void
    {
        $upload = $this->uploadRepository->findById(Uuid::fromString($input->uploadId));

        if ($upload === null || (string) $upload->organizationId !== $input->organizationId) {
            throw new UploadNotFoundException($input->uploadId);
        }

        $aborted = $upload->abort();
        $this->uploadRepository->update($aborted);

        try {
            $generatedName = (string) $upload->id.'.'.$upload->mimeType->extension();
            $key = $this->mediaStorage->generatePath((string) $upload->organizationId, $generatedName);
            $this->chunkedStorage->abort($upload->s3UploadId, $key);
        } catch (\Throwable) {
            // Best-effort S3 cleanup
        }
    }
}
