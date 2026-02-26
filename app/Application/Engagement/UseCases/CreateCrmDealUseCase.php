<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\CreateCrmDealInput;
use App\Application\Engagement\DTOs\CrmSyncResultOutput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Entities\CrmSyncLog;
use App\Domain\Engagement\Events\CrmDealCreated;
use App\Domain\Engagement\Events\CrmSyncFailed;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmEntityType;
use App\Domain\Engagement\ValueObjects\CrmSyncDirection;
use App\Domain\Engagement\ValueObjects\CrmSyncResult;
use App\Domain\Engagement\ValueObjects\CrmSyncStatus;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateCrmDealUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmSyncLogRepositoryInterface $syncLogRepository,
        private readonly CrmConnectorFactoryInterface $connectorFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateCrmDealInput $input): CrmSyncResultOutput
    {
        $connectionId = Uuid::fromString($input->connectionId);
        $connection = $this->connectionRepository->findById($connectionId);

        if ($connection === null || (string) $connection->organizationId !== $input->organizationId) {
            throw new CrmConnectionNotFoundException($input->connectionId);
        }

        $connector = $this->connectorFactory->make($connection->provider);

        $dealData = array_filter([
            'name' => $input->dealName,
            'contact_external_id' => $input->contactExternalId,
            'amount' => $input->amount,
            'stage' => $input->stage,
            'campaign_id' => $input->campaignId,
        ], fn ($v) => $v !== null);

        try {
            $apiResult = $connector->createDeal($connection->accessToken, $dealData);

            $result = CrmSyncResult::success(
                direction: CrmSyncDirection::Outbound,
                entityType: CrmEntityType::Deal,
                action: 'create',
                externalId: $apiResult['id'],
            );

            $this->logSync($connection->organizationId, $connectionId, $result, $dealData);

            $connection = $connection->recordSync();
            $this->connectionRepository->update($connection);

            $this->eventDispatcher->dispatch(new CrmDealCreated(
                aggregateId: (string) $connection->id,
                organizationId: $input->organizationId,
                userId: $input->userId,
                connectionId: $input->connectionId,
                externalDealId: $apiResult['id'],
            ));

            return CrmSyncResultOutput::fromValueObject($result);
        } catch (\Throwable $e) {
            $result = CrmSyncResult::failed(
                direction: CrmSyncDirection::Outbound,
                entityType: CrmEntityType::Deal,
                action: 'create',
                errorMessage: $e->getMessage(),
            );

            $this->logSync($connection->organizationId, $connectionId, $result, $dealData);

            $this->eventDispatcher->dispatch(new CrmSyncFailed(
                aggregateId: (string) $connection->id,
                organizationId: $input->organizationId,
                userId: $input->userId,
                connectionId: $input->connectionId,
                entityType: CrmEntityType::Deal->value,
                errorMessage: $e->getMessage(),
            ));

            return CrmSyncResultOutput::fromValueObject($result);
        }
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
}
