<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Application\Media\DTOs\ChunkReceivedOutput;
use App\Application\Media\DTOs\UploadChunkInput;
use App\Application\Media\Exceptions\UploadNotFoundException;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class UploadChunkUseCase
{
    public function __construct(
        private readonly MediaUploadRepositoryInterface $uploadRepository,
        private readonly ChunkedStorageInterface $chunkedStorage,
        private readonly MediaStorageInterface $mediaStorage,
    ) {}

    public function execute(UploadChunkInput $input): ChunkReceivedOutput
    {
        $upload = $this->uploadRepository->findById(Uuid::fromString($input->uploadId));

        if ($upload === null || (string) $upload->organizationId !== $input->organizationId) {
            throw new UploadNotFoundException($input->uploadId);
        }

        $generatedName = (string) $upload->id.'.'.$upload->mimeType->extension();
        $key = $this->mediaStorage->generatePath((string) $upload->organizationId, $generatedName);

        $etag = $this->chunkedStorage->uploadPart(
            s3UploadId: $upload->s3UploadId,
            key: $key,
            partNumber: $input->chunkIndex,
            data: $input->data,
        );

        $upload = $upload->receiveChunk($input->chunkIndex, $etag);

        $this->uploadRepository->update($upload);

        return new ChunkReceivedOutput(
            chunkIndex: $input->chunkIndex,
            receivedCount: count($upload->receivedChunks),
            totalChunks: $upload->totalChunks,
            allChunksReceived: $upload->allChunksReceived(),
        );
    }
}
