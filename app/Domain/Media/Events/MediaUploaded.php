<?php

declare(strict_types=1);

namespace App\Domain\Media\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class MediaUploaded extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $fileName,
        public string $mimeType,
        public int $fileSize,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'media.uploaded';
    }
}
