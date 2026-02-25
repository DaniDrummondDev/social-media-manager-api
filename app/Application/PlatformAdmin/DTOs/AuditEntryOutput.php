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

    /**
     * @param  \App\Domain\PlatformAdmin\Entities\AdminAuditEntry  $entry
     * @param  array<string, mixed>|null  $adminInfo
     */
    public static function fromEntity(
        \App\Domain\PlatformAdmin\Entities\AdminAuditEntry $entry,
        ?array $adminInfo = null,
    ): self {
        return new self(
            id: (string) $entry->id,
            action: $entry->action,
            admin: $adminInfo,
            resourceType: $entry->resourceType,
            resourceId: $entry->resourceId,
            context: $entry->context,
            ipAddress: $entry->ipAddress,
            createdAt: $entry->createdAt->format('Y-m-d\TH:i:s\Z'),
        );
    }
}
