<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\Entities\CrmConnection;

final readonly class CrmConnectionOutput
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $provider,
        public string $externalAccountId,
        public string $accountName,
        public string $status,
        public ?string $lastSyncAt,
        public string $connectedBy,
        public string $createdAt,
        public string $updatedAt,
        public ?string $disconnectedAt,
    ) {}

    public static function fromEntity(CrmConnection $connection): self
    {
        return new self(
            id: (string) $connection->id,
            organizationId: (string) $connection->organizationId,
            provider: $connection->provider->value,
            externalAccountId: $connection->externalAccountId,
            accountName: $connection->accountName,
            status: $connection->status->value,
            lastSyncAt: $connection->lastSyncAt?->format('c'),
            connectedBy: (string) $connection->connectedBy,
            createdAt: $connection->createdAt->format('c'),
            updatedAt: $connection->updatedAt->format('c'),
            disconnectedAt: $connection->disconnectedAt?->format('c'),
        );
    }
}
