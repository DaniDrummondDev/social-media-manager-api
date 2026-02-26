<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\Contracts\CrmOAuthStateServiceInterface;
use App\Application\Engagement\DTOs\CrmConnectionOutput;
use App\Application\Engagement\DTOs\HandleCrmCallbackInput;
use App\Application\Engagement\Exceptions\CrmOAuthStateInvalidException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Engagement\Entities\CrmConnection;
use App\Domain\Engagement\Exceptions\CrmConnectionAlreadyExistsException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class HandleCrmCallbackUseCase
{
    public function __construct(
        private readonly CrmOAuthStateServiceInterface $stateService,
        private readonly CrmConnectorFactoryInterface $connectorFactory,
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(HandleCrmCallbackInput $input): CrmConnectionOutput
    {
        $stateData = $this->stateService->validateAndConsumeState($input->state);

        if ($stateData === null) {
            throw new CrmOAuthStateInvalidException;
        }

        $organizationId = Uuid::fromString($stateData['organizationId']);
        $userId = Uuid::fromString($stateData['userId']);
        $provider = CrmProvider::from($stateData['provider']);

        $existing = $this->connectionRepository->findByOrganizationAndProvider($organizationId, $provider);
        if ($existing !== null) {
            throw new CrmConnectionAlreadyExistsException;
        }

        $connector = $this->connectorFactory->make($provider);
        $credentials = $connector->authenticate($input->code, $input->state);

        $connection = CrmConnection::create(
            organizationId: $organizationId,
            provider: $provider,
            accessToken: $credentials['access_token'],
            refreshToken: $credentials['refresh_token'],
            tokenExpiresAt: $credentials['expires_at'] !== null
                ? new DateTimeImmutable($credentials['expires_at'])
                : null,
            externalAccountId: $credentials['account_id'],
            accountName: $credentials['account_name'],
            connectedBy: $userId,
        );

        $this->connectionRepository->create($connection);
        $this->eventDispatcher->dispatch(...$connection->domainEvents);

        return CrmConnectionOutput::fromEntity($connection);
    }
}
