<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class AuditEntryOutput
{
    public function __construct(
        public string $id,
        public string $action,
        public ?array $admin,
        public string $resourceType,
        public ?string $resourceId,
        public array $context,
        public string $ipAddress,
        public string $createdAt,
    ) {}
}
