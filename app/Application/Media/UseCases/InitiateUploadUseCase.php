<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\InitiateUploadInput;
use App\Application\Media\DTOs\InitiateUploadOutput;
use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\ValueObjects\Uuid;

final class InitiateUploadUseCase
{
    public function __construct(
        private readonly MediaUploadRepositoryInterface $uploadRepository,
        private readonly ChunkedStorageInterface $chunkedStorage,
        private readonly MediaStorageInterface $mediaStorage,
    ) {}

    public function execute(InitiateUploadInput $input): InitiateUploadOutput
    {
        $mimeType = MimeType::fromString($input->mimeType);

        $upload = MediaUpload::create(
            organizationId: Uuid::fromString($input->organizationId),
            userId: Uuid::fromString($input->userId),
            fileName: $input->fileName,
            mimeType: $mimeType,
            totalBytes: $input->totalBytes,
            chunkSizeBytes: $input->chunkSizeBytes ?? MediaUpload::DEFAULT_CHUNK_SIZE,
        );

        $generatedName = (string) $upload->id.'.'.$mimeType->extension();
        $key = $this->mediaStorage->generatePath($input->organizationId, $generatedName);

        $s3UploadId = $this->chunkedStorage->initiate($key, $mimeType->value);
        $upload = $upload->setS3UploadId($s3UploadId);

        $this->uploadRepository->create($upload);

        return new InitiateUploadOutput(
            uploadId: (string) $upload->id,
            s3UploadId: $s3UploadId,
            chunkSizeBytes: $upload->chunkSizeBytes,
            totalChunks: $upload->totalChunks,
            expiresAt: $upload->expiresAt->format('c'),
        );
    }
}
