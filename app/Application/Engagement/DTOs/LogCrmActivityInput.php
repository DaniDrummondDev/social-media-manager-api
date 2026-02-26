<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class LogCrmActivityInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $connectionId,
        public string $entityId,
        public string $activityType,
        public string $description,
        public ?string $relatedContentId = null,
    ) {}
}
