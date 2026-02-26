<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\CrmConnectionOutput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class TestCrmConnectionUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmConnectorFactoryInterface $connectorFactory,
    ) {}

    public function execute(string $organizationId, string $connectionId): CrmConnectionOutput
    {
        $id = Uuid::fromString($connectionId);
        $connection = $this->connectionRepository->findById($id);

        if ($connection === null || (string) $connection->organizationId !== $organizationId) {
            throw new CrmConnectionNotFoundException($connectionId);
        }

        $connector = $this->connectorFactory->make($connection->provider);
        $isHealthy = $connector->getConnectionStatus($connection->accessToken);

        if (! $isHealthy) {
            $connection = $connection->markError();
            $this->connectionRepository->update($connection);
        }

        return CrmConnectionOutput::fromEntity($connection);
    }
}
