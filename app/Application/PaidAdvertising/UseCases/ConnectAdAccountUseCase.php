<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\Contracts\AdOAuthStateServiceInterface;
use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\DTOs\ConnectAdAccountInput;
use App\Application\PaidAdvertising\DTOs\ConnectAdAccountOutput;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;

final class ConnectAdAccountUseCase
{
    public function __construct(
        private readonly AdOAuthStateServiceInterface $stateService,
        private readonly AdPlatformFactoryInterface $platformFactory,
    ) {}

    public function execute(ConnectAdAccountInput $input): ConnectAdAccountOutput
    {
        $provider = AdProvider::from($input->provider);
        $adapter = $this->platformFactory->make($provider);

        $state = $this->stateService->generateState(
            $input->organizationId,
            $input->userId,
            $input->provider,
        );

        $result = $adapter->connect(
            redirectUri: $state,
            state: $state,
        );

        return new ConnectAdAccountOutput(
            authorizationUrl: $result['auth_url'],
            state: $state,
        );
    }
}
