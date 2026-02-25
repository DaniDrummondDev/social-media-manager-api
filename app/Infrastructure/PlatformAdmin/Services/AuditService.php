<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Services;

use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\Entities\AdminAuditEntry;
use App\Domain\PlatformAdmin\Repositories\AdminAuditEntryRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class AuditService implements AuditServiceInterface
{
    public function __construct(
        private readonly AdminAuditEntryRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function log(
        string $adminId,
        string $action,
        string $resourceType,
        ?string $resourceId,
        array $context,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        $entry = AdminAuditEntry::create(
            adminId: Uuid::fromString($adminId),
            action: $action,
            resourceType: $resourceType,
            resourceId: $resourceId,
            context: $context,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        $this->repository->create($entry);
    }
}
