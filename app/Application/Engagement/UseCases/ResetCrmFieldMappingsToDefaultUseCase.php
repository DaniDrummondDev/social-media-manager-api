<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\CrmFieldMappingOutput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Events\CrmFieldMappingUpdated;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ResetCrmFieldMappingsToDefaultUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmFieldMappingRepositoryInterface $fieldMappingRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return array<CrmFieldMappingOutput>
     */
    public function execute(string $organizationId, string $userId, string $connectionId): array
    {
        $connId = Uuid::fromString($connectionId);
        $connection = $this->connectionRepository->findById($connId);

        if ($connection === null || (string) $connection->organizationId !== $organizationId) {
            throw new CrmConnectionNotFoundException($connectionId);
        }

        $this->fieldMappingRepository->resetToDefault($connId, $connection->provider);

        $defaults = $this->fieldMappingRepository->findDefaultByProvider($connection->provider);

        $this->eventDispatcher->dispatch(new CrmFieldMappingUpdated(
            aggregateId: (string) $connection->id,
            organizationId: $organizationId,
            userId: $userId,
            connectionId: $connectionId,
            provider: $connection->provider->value,
        ));

        return array_map(
            fn ($mapping) => CrmFieldMappingOutput::fromValueObject($mapping),
            $defaults,
        );
    }
}
