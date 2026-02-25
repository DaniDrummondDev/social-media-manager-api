<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Contracts;

interface AuditServiceInterface
{
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
    ): void;
}
