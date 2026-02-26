<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DisconnectCrmUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmConnectorFactoryInterface $connectorFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(string $organizationId, string $userId, string $connectionId): void
    {
        $id = Uuid::fromString($connectionId);
        $connection = $this->connectionRepository->findById($id);

        if ($connection === null || (string) $connection->organizationId !== $organizationId) {
            throw new CrmConnectionNotFoundException($connectionId);
        }

        $connector = $this->connectorFactory->make($connection->provider);

        try {
            $connector->revokeToken($connection->accessToken);
        } catch (\Throwable) {
            // Best-effort revocation — continue with disconnect even if revoke fails
        }

        $connection = $connection->disconnect($userId);

        $this->connectionRepository->update($connection);
        $this->eventDispatcher->dispatch(...$connection->domainEvents);
    }
}
