<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Domain\Engagement\Entities\CrmSyncLog;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmEntityType;
use App\Domain\Engagement\ValueObjects\CrmSyncDirection;
use App\Domain\Engagement\ValueObjects\CrmSyncStatus;
use App\Domain\Shared\ValueObjects\Uuid;

final class ProcessCrmWebhookUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmSyncLogRepositoryInterface $syncLogRepository,
    ) {}

    /**
     * Process an inbound webhook from the CRM.
     *
     * @param  array<string, mixed>  $payload
     */
    public function execute(string $organizationId, string $connectionId, string $eventType, array $payload): void
    {
        $connId = Uuid::fromString($connectionId);
        $connection = $this->connectionRepository->findById($connId);

        if ($connection === null || (string) $connection->organizationId !== $organizationId) {
            throw new CrmConnectionNotFoundException($connectionId);
        }

        $entityType = match (true) {
            str_starts_with($eventType, 'contact.') => CrmEntityType::Contact,
            str_starts_with($eventType, 'deal.') => CrmEntityType::Deal,
            default => CrmEntityType::Activity,
        };

        $log = CrmSyncLog::create(
            organizationId: $connection->organizationId,
            connectionId: $connId,
            direction: CrmSyncDirection::Inbound,
            entityType: $entityType,
            action: $eventType,
            status: CrmSyncStatus::Success,
            externalId: $payload['id'] ?? null,
            payload: $payload,
        );

        $this->syncLogRepository->create($log);

        $connection = $connection->recordSync();
        $this->connectionRepository->update($connection);
    }
}
