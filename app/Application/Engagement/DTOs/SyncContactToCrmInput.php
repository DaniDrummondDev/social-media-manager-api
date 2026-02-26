<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class SyncContactToCrmInput
{
    /**
     * @param  array<string, mixed>|null  $customFields
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $connectionId,
        public string $authorName,
        public string $authorExternalId,
        public ?string $email = null,
        public ?string $sentiment = null,
        public ?string $network = null,
        public ?string $campaignName = null,
        public ?string $contentTitle = null,
        public ?array $customFields = null,
    ) {}
}
