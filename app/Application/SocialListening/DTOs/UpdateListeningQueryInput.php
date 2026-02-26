<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class UpdateListeningQueryInput
{
    /**
     * @param  array<string>|null  $platforms
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $queryId,
        public ?string $name = null,
        public ?string $value = null,
        public ?array $platforms = null,
    ) {}
}
