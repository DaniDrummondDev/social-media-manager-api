<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases;

use App\Application\Media\DTOs\ScanMediaInput;
use App\Application\Media\Exceptions\MediaNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ScanMediaUseCase
{
    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ScanMediaInput $input): void
    {
        $media = $this->mediaRepository->findById(Uuid::fromString($input->mediaId));

        if ($media === null) {
            throw new MediaNotFoundException($input->mediaId);
        }

        $scanned = match ($input->scanResult) {
            'clean' => $media->markAsClean(),
            'rejected' => $media->markAsRejected(),
            default => throw new \InvalidArgumentException("Invalid scan result: {$input->scanResult}"),
        };

        $this->mediaRepository->update($scanned);
        $this->eventDispatcher->dispatch(...$scanned->domainEvents);
    }
}
