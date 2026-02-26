<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Entities;

use App\Domain\Engagement\ValueObjects\CrmEntityType;
use App\Domain\Engagement\ValueObjects\CrmSyncDirection;
use App\Domain\Engagement\ValueObjects\CrmSyncStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class CrmSyncLog
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $connectionId,
        public CrmSyncDirection $direction,
        public CrmEntityType $entityType,
        public string $action,
        public CrmSyncStatus $status,
        public ?string $externalId,
        public ?string $errorMessage,
        public ?array $payload,
        public DateTimeImmutable $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function create(
        Uuid $organizationId,
        Uuid $connectionId,
        CrmSyncDirection $direction,
        CrmEntityType $entityType,
        string $action,
        CrmSyncStatus $status,
        ?string $externalId = null,
        ?string $errorMessage = null,
        ?array $payload = null,
    ): self {
        return new self(
            id: Uuid::generate(),
            organizationId: $organizationId,
            connectionId: $connectionId,
            direction: $direction,
            entityType: $entityType,
            action: $action,
            status: $status,
            externalId: $externalId,
            errorMessage: $errorMessage,
            payload: $payload,
            createdAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $connectionId,
        CrmSyncDirection $direction,
        CrmEntityType $entityType,
        string $action,
        CrmSyncStatus $status,
        ?string $externalId,
        ?string $errorMessage,
        ?array $payload,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            connectionId: $connectionId,
            direction: $direction,
            entityType: $entityType,
            action: $action,
            status: $status,
            externalId: $externalId,
            errorMessage: $errorMessage,
            payload: $payload,
            createdAt: $createdAt,
        );
    }

    public function markFailed(string $errorMessage): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectionId: $this->connectionId,
            direction: $this->direction,
            entityType: $this->entityType,
            action: $this->action,
            status: CrmSyncStatus::Failed,
            externalId: $this->externalId,
            errorMessage: $errorMessage,
            payload: $this->payload,
            createdAt: $this->createdAt,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }
}
