<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class ConnectCrmWithApiKeyInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $provider,
        public string $apiKey,
        public string $accountName,
    ) {}
}
