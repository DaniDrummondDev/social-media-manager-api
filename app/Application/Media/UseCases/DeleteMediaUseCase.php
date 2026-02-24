<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\DeleteMediaInput;
use App\Application\Media\Exceptions\MediaNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeleteMediaUseCase
{
    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteMediaInput $input): void
    {
        $media = $this->mediaRepository->findById(Uuid::fromString($input->mediaId));

        if ($media === null || (string) $media->organizationId !== $input->organizationId) {
            throw new MediaNotFoundException($input->mediaId);
        }

        $deleted = $media->softDelete();

        $this->mediaRepository->update($deleted);
        $this->eventDispatcher->dispatch(...$deleted->domainEvents);
    }
}
