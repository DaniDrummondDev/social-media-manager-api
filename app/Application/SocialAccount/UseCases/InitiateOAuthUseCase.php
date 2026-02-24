<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\UseCases;

use App\Application\SocialAccount\Contracts\OAuthStateServiceInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Application\SocialAccount\DTOs\InitiateOAuthInput;
use App\Application\SocialAccount\DTOs\InitiateOAuthOutput;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

final class InitiateOAuthUseCase
{
    public function __construct(
        private readonly OAuthStateServiceInterface $stateService,
        private readonly SocialAccountAdapterFactoryInterface $adapterFactory,
    ) {}

    public function execute(InitiateOAuthInput $input): InitiateOAuthOutput
    {
        $provider = SocialProvider::from($input->provider);
        $adapter = $this->adapterFactory->make($provider);

        $state = $this->stateService->generateState($input->organizationId, $input->userId, $input->provider);
        $authorizationUrl = $adapter->getAuthorizationUrl($state, $input->scopes);

        return new InitiateOAuthOutput(
            authorizationUrl: $authorizationUrl,
            state: $state,
        );
    }
}
