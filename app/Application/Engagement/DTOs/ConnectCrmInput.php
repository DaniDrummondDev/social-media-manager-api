<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class ConnectCrmInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $provider,
    ) {}
}
