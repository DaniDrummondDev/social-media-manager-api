<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\ConnectCrmWithApiKeyInput;
use App\Application\Engagement\DTOs\CrmConnectionOutput;
use App\Application\Engagement\Exceptions\CrmApiKeyInvalidException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Entities\CrmConnection;
use App\Domain\Engagement\Exceptions\CrmConnectionAlreadyExistsException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;

final class ConnectCrmWithApiKeyUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmConnectorFactoryInterface $connectorFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ConnectCrmWithApiKeyInput $input): CrmConnectionOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $userId = Uuid::fromString($input->userId);
        $provider = CrmProvider::from($input->provider);

        $existing = $this->connectionRepository->findByOrganizationAndProvider($organizationId, $provider);
        if ($existing !== null) {
            throw new CrmConnectionAlreadyExistsException;
        }

        $connector = $this->connectorFactory->make($provider);

        $isValid = $connector->getConnectionStatus($input->apiKey);
        if (! $isValid) {
            throw new CrmApiKeyInvalidException;
        }

        $connection = CrmConnection::create(
            organizationId: $organizationId,
            provider: $provider,
            accessToken: $input->apiKey,
            refreshToken: null,
            tokenExpiresAt: null,
            externalAccountId: $provider->value,
            accountName: $input->accountName,
            connectedBy: $userId,
        );

        $this->connectionRepository->create($connection);
        $this->eventDispatcher->dispatch(...$connection->domainEvents);

        return CrmConnectionOutput::fromEntity($connection);
    }
}
