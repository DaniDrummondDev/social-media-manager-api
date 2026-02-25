<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class ListAuditLogInput
{
    public function __construct(
        public ?string $action = null,
        public ?string $adminId = null,
        public ?string $resourceType = null,
        public ?string $resourceId = null,
        public ?string $from = null,
        public ?string $to = null,
        public string $sort = '-created_at',
        public int $perPage = 20,
        public ?string $cursor = null,
    ) {}
}
