<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class DeleteListeningQueryInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $queryId,
    ) {}
}
