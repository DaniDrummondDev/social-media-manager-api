<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\MediaOutput;
use App\Application\Media\Exceptions\MediaNotFoundException;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\Compatibility;
use App\Domain\Shared\ValueObjects\Uuid;

final class CalculateCompatibilityUseCase
{
    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
    ) {}

    public function execute(string $mediaId): MediaOutput
    {
        $media = $this->mediaRepository->findById(Uuid::fromString($mediaId));

        if ($media === null) {
            throw new MediaNotFoundException($mediaId);
        }

        $compatibility = Compatibility::calculate(
            mimeType: $media->mimeType,
            fileSize: $media->fileSize,
            dimensions: $media->dimensions,
            durationSeconds: $media->durationSeconds,
        );

        $updated = $media->setCompatibility($compatibility);

        if ($updated !== $media) {
            $this->mediaRepository->update($updated);
        }

        return MediaOutput::fromEntity($updated);
    }
}
