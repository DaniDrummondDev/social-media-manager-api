<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\Entities\CrmSyncLog;

final readonly class CrmSyncLogOutput
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $connectionId,
        public string $direction,
        public string $entityType,
        public string $action,
        public string $status,
        public ?string $externalId,
        public ?string $errorMessage,
        public ?array $payload,
        public string $createdAt,
    ) {}

    public static function fromEntity(CrmSyncLog $log): self
    {
        return new self(
            id: (string) $log->id,
            organizationId: (string) $log->organizationId,
            connectionId: (string) $log->connectionId,
            direction: $log->direction->value,
            entityType: $log->entityType->value,
            action: $log->action,
            status: $log->status->value,
            externalId: $log->externalId,
            errorMessage: $log->errorMessage,
            payload: $log->payload,
            createdAt: $log->createdAt->format('c'),
        );
    }
}
