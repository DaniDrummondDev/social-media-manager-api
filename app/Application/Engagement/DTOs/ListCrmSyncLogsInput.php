<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class ListCrmSyncLogsInput
{
    public function __construct(
        public string $organizationId,
        public string $connectionId,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
