<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\CrmFieldMappingOutput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetCrmFieldMappingsUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmFieldMappingRepositoryInterface $fieldMappingRepository,
    ) {}

    /**
     * @return array<CrmFieldMappingOutput>
     */
    public function execute(string $organizationId, string $connectionId): array
    {
        $connId = Uuid::fromString($connectionId);
        $connection = $this->connectionRepository->findById($connId);

        if ($connection === null || (string) $connection->organizationId !== $organizationId) {
            throw new CrmConnectionNotFoundException($connectionId);
        }

        $mappings = $this->fieldMappingRepository->findByConnectionId($connId);

        if (empty($mappings)) {
            $mappings = $this->fieldMappingRepository->findDefaultByProvider($connection->provider);
        }

        return array_map(
            fn ($mapping) => CrmFieldMappingOutput::fromValueObject($mapping),
            $mappings,
        );
    }
}
