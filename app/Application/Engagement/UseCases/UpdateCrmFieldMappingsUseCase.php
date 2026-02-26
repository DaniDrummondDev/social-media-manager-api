<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\CrmFieldMappingOutput;
use App\Application\Engagement\DTOs\UpdateCrmFieldMappingsInput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Events\CrmFieldMappingUpdated;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmFieldMapping;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateCrmFieldMappingsUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmFieldMappingRepositoryInterface $fieldMappingRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return array<CrmFieldMappingOutput>
     */
    public function execute(UpdateCrmFieldMappingsInput $input): array
    {
        $connId = Uuid::fromString($input->connectionId);
        $connection = $this->connectionRepository->findById($connId);

        if ($connection === null || (string) $connection->organizationId !== $input->organizationId) {
            throw new CrmConnectionNotFoundException($input->connectionId);
        }

        $mappings = [];
        foreach ($input->mappings as $index => $mappingData) {
            $mappings[] = CrmFieldMapping::create(
                smmField: $mappingData['smm_field'],
                crmField: $mappingData['crm_field'],
                transform: $mappingData['transform'] ?? null,
                position: $index,
            );
        }

        $this->fieldMappingRepository->saveForConnection($connId, $mappings);

        $this->eventDispatcher->dispatch(new CrmFieldMappingUpdated(
            aggregateId: (string) $connection->id,
            organizationId: $input->organizationId,
            userId: $input->userId,
            connectionId: $input->connectionId,
            provider: $connection->provider->value,
        ));

        return array_map(
            fn ($mapping) => CrmFieldMappingOutput::fromValueObject($mapping),
            $mappings,
        );
    }
}
