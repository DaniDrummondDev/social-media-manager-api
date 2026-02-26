<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\CrmConnectorFactoryInterface;
use App\Application\Engagement\Contracts\CrmOAuthStateServiceInterface;
use App\Application\Engagement\DTOs\ConnectCrmInput;
use App\Application\Engagement\DTOs\ConnectCrmOutput;
use App\Domain\Engagement\Exceptions\CrmConnectionAlreadyExistsException;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;

final class ConnectCrmUseCase
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
        private readonly CrmConnectorFactoryInterface $connectorFactory,
        private readonly CrmOAuthStateServiceInterface $stateService,
    ) {}

    public function execute(ConnectCrmInput $input): ConnectCrmOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $provider = CrmProvider::from($input->provider);

        $existing = $this->connectionRepository->findByOrganizationAndProvider($organizationId, $provider);
        if ($existing !== null) {
            throw new CrmConnectionAlreadyExistsException;
        }

        $state = $this->stateService->generateState($input->organizationId, $input->userId, $input->provider);

        $connector = $this->connectorFactory->make($provider);
        $authorizationUrl = $connector->getAuthorizationUrl($state);

        return new ConnectCrmOutput(
            authorizationUrl: $authorizationUrl,
            state: $state,
        );
    }
}
