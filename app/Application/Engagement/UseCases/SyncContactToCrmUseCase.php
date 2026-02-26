<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\CrmSyncResultOutput;
use App\Application\Engagement\DTOs\SyncContactToCrmInput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Entities\CrmSyncLog;
use App\Domain\Engagement\Events\CrmContactSynced;
use App\Domain\Engagement\Events\CrmSyncFailed;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmEntityType;
use App\Domain\Engagement\ValueObjects\CrmSyncDirection;
use App\Domain\Engagement\ValueObjects\CrmSyncResult;
use App\Domain\Engagement\ValueObjects\CrmSyncStatus;
use App\Domain\Shared\ValueObjects\Uuid;

final class SyncContactToCrmUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmFieldMappingRepositoryInterface $fieldMappingRepository,
        private readonly CrmSyncLogRepositoryInterface $syncLogRepository,
        private readonly CrmConnectorFactoryInterface $connectorFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(SyncContactToCrmInput $input): CrmSyncResultOutput
    {
        $connectionId = Uuid::fromString($input->connectionId);
        $connection = $this->connectionRepository->findById($connectionId);

        if ($connection === null || (string) $connection->organizationId !== $input->organizationId) {
            throw new CrmConnectionNotFoundException($input->connectionId);
        }

        $connector = $this->connectorFactory->make($connection->provider);
        $mappings = $this->fieldMappingRepository->findByConnectionId($connectionId);
        $contactData = $this->applyFieldMappings($this->buildContactData($input), $mappings);

        try {
            $existingContacts = $connector->searchContacts($connection->accessToken, $input->authorExternalId);

            if (! empty($existingContacts)) {
                $crmContactId = $existingContacts[0]['id'];
                $apiResult = $connector->updateContact($connection->accessToken, $crmContactId, $contactData);
                $action = 'update';
            } else {
                $apiResult = $connector->createContact($connection->accessToken, $contactData);
                $action = 'create';
            }

            $result = CrmSyncResult::success(
                direction: CrmSyncDirection::Outbound,
                entityType: CrmEntityType::Contact,
                action: $action,
                externalId: $apiResult['id'],
            );

            $this->logSync($connection->organizationId, $connectionId, $result, $contactData);
            $this->recordSync($connectionId);

            $this->eventDispatcher->dispatch(new CrmContactSynced(
                aggregateId: (string) $connection->id,
                organizationId: $input->organizationId,
                userId: $input->userId,
                connectionId: $input->connectionId,
                externalContactId: $apiResult['id'],
                direction: CrmSyncDirection::Outbound->value,
            ));

            return CrmSyncResultOutput::fromValueObject($result);
        } catch (\Throwable $e) {
            $result = CrmSyncResult::failed(
                direction: CrmSyncDirection::Outbound,
                entityType: CrmEntityType::Contact,
                action: 'sync',
                errorMessage: $e->getMessage(),
            );

            $this->logSync($connection->organizationId, $connectionId, $result, $contactData);

            $this->eventDispatcher->dispatch(new CrmSyncFailed(
                aggregateId: (string) $connection->id,
                organizationId: $input->organizationId,
                userId: $input->userId,
                connectionId: $input->connectionId,
                entityType: CrmEntityType::Contact->value,
                errorMessage: $e->getMessage(),
            ));

            return CrmSyncResultOutput::fromValueObject($result);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContactData(SyncContactToCrmInput $input): array
    {
        return array_filter([
            'name' => $input->authorName,
            'external_id' => $input->authorExternalId,
            'email' => $input->email,
            'sentiment' => $input->sentiment,
            'network' => $input->network,
            'campaign_name' => $input->campaignName,
            'content_title' => $input->contentTitle,
            ...($input->customFields ?? []),
        ], fn ($v) => $v !== null);
    }

    /**
     * @param  array<string, mixed>  $sourceData
     * @param  array<\App\Domain\Engagement\ValueObjects\CrmFieldMapping>  $mappings
     * @return array<string, mixed>
     */
    private function applyFieldMappings(array $sourceData, array $mappings): array
    {
        if (empty($mappings)) {
            return $sourceData;
        }

        $mapped = [];

        foreach ($mappings as $mapping) {
            $value = $sourceData[$mapping->smmField] ?? null;

            if ($value === null) {
                continue;
            }

            if ($mapping->hasTransform()) {
                $value = $this->applyTransform($value, $mapping->transform);
            }

            $mapped[$mapping->crmField] = $value;
        }

        return $mapped;
    }

    private function applyTransform(mixed $value, ?string $transform): mixed
    {
        return match ($transform) {
            'uppercase' => mb_strtoupper((string) $value),
            'lowercase' => mb_strtolower((string) $value),
            'trim' => trim((string) $value),
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function logSync(Uuid $organizationId, Uuid $connectionId, CrmSyncResult $result, ?array $payload = null): void
    {
        $log = CrmSyncLog::create(
            organizationId: $organizationId,
            connectionId: $connectionId,
            direction: $result->direction,
            entityType: $result->entityType,
            action: $result->action,
            status: $result->status,
            externalId: $result->externalId,
            errorMessage: $result->errorMessage,
            payload: $payload,
        );

        $this->syncLogRepository->create($log);
    }

    private function recordSync(Uuid $connectionId): void
    {
        $connection = $this->connectionRepository->findById($connectionId);

        if ($connection !== null) {
            $connection = $connection->recordSync();
            $this->connectionRepository->update($connection);
        }
    }
}
