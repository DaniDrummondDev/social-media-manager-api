<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\DTOs\CrmConnectionOutput;
use App\Application\Engagement\Exceptions\CrmConnectionNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class RefreshCrmTokenUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmConnectorFactoryInterface $connectorFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(string $connectionId): CrmConnectionOutput
    {
        $id = Uuid::fromString($connectionId);
        $connection = $this->connectionRepository->findById($id);

        if ($connection === null) {
            throw new CrmConnectionNotFoundException($connectionId);
        }

        if ($connection->refreshToken === null) {
            $connection = $connection->markTokenExpired('system');
            $this->connectionRepository->update($connection);
            $this->eventDispatcher->dispatch(...$connection->domainEvents);

            return CrmConnectionOutput::fromEntity($connection);
        }

        $connector = $this->connectorFactory->make($connection->provider);

        try {
            $newTokens = $connector->refreshToken($connection->refreshToken);

            $connection = $connection->refreshTokens(
                accessToken: $newTokens['access_token'],
                refreshToken: $newTokens['refresh_token'],
                tokenExpiresAt: $newTokens['expires_at'] !== null
                    ? new DateTimeImmutable($newTokens['expires_at'])
                    : null,
            );
        } catch (\Throwable) {
            $connection = $connection->markTokenExpired('system');
            $this->eventDispatcher->dispatch(...$connection->domainEvents);
        }

        $this->connectionRepository->update($connection);

        return CrmConnectionOutput::fromEntity($connection);
    }
}
