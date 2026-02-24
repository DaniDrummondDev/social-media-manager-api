<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\MediaListOutput;
use App\Application\Media\DTOs\MediaOutput;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListMediaUseCase
{
    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
    ) {}

    public function execute(string $organizationId): MediaListOutput
    {
        $mediaItems = $this->mediaRepository->findByOrganizationId(
            Uuid::fromString($organizationId),
        );

        $outputs = array_map(
            fn ($media) => MediaOutput::fromEntity($media),
            $mediaItems,
        );

        return new MediaListOutput(items: $outputs);
    }
}
