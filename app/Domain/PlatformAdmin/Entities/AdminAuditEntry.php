<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Entities;

use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AdminAuditEntry
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public Uuid $id,
        public Uuid $adminId,
        public string $action,
        public string $resourceType,
        public ?string $resourceId,
        public array $context,
        public string $ipAddress,
        public ?string $userAgent,
        public DateTimeImmutable $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public static function create(
        Uuid $adminId,
        string $action,
        string $resourceType,
        ?string $resourceId,
        array $context,
        string $ipAddress,
        ?string $userAgent,
    ): self {
        return new self(
            id: Uuid::generate(),
            adminId: $adminId,
            action: $action,
            resourceType: $resourceType,
            resourceId: $resourceId,
            context: $context,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            createdAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $adminId,
        string $action,
        string $resourceType,
        ?string $resourceId,
        array $context,
        string $ipAddress,
        ?string $userAgent,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            adminId: $adminId,
            action: $action,
            resourceType: $resourceType,
            resourceId: $resourceId,
            context: $context,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            createdAt: $createdAt,
        );
    }
}
