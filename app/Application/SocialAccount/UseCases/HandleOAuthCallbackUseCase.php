<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialAccount\Contracts\OAuthStateServiceInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Application\SocialAccount\DTOs\HandleOAuthCallbackInput;
use App\Application\SocialAccount\DTOs\SocialAccountOutput;
use App\Application\SocialAccount\Exceptions\OAuthStateInvalidException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Exceptions\SocialAccountAlreadyConnectedException;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

final class HandleOAuthCallbackUseCase
{
    public function __construct(
        private readonly OAuthStateServiceInterface $stateService,
        private readonly SocialAccountAdapterFactoryInterface $adapterFactory,
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(HandleOAuthCallbackInput $input): SocialAccountOutput
    {
        $stateData = $this->stateService->validateAndConsumeState($input->state);

        if ($stateData === null) {
            throw new OAuthStateInvalidException;
        }

        $organizationId = Uuid::fromString($stateData['organizationId']);
        $userId = Uuid::fromString($stateData['userId']);
        $provider = SocialProvider::from($stateData['provider']);

        $adapter = $this->adapterFactory->make($provider);
        $credentials = $adapter->handleCallback($input->code, $input->state);
        $accountInfo = $adapter->getAccountInfo($credentials->accessToken);

        $providerUserId = $accountInfo['id'];
        $username = $accountInfo['username'];
        $displayName = $accountInfo['display_name'] ?? null;
        $profilePictureUrl = $accountInfo['profile_picture_url'] ?? null;

        $existing = $this->socialAccountRepository->findByProviderAndProviderUserId($provider, $providerUserId);

        if ($existing !== null) {
            if ((string) $existing->organizationId !== (string) $organizationId) {
                throw new SocialAccountAlreadyConnectedException($provider->value);
            }

            $updated = $existing->reconnect($credentials, (string) $userId);
            $updated = $updated->updateProfile($username, $displayName, $profilePictureUrl);

            $this->socialAccountRepository->update($updated);
            $this->eventDispatcher->dispatch(...$updated->domainEvents);

            return SocialAccountOutput::fromEntity($updated);
        }

        $account = SocialAccount::create(
            organizationId: $organizationId,
            connectedBy: $userId,
            provider: $provider,
            providerUserId: $providerUserId,
            username: $username,
            credentials: $credentials,
            displayName: $displayName,
            profilePictureUrl: $profilePictureUrl,
        );

        $this->socialAccountRepository->create($account);
        $this->eventDispatcher->dispatch(...$account->domainEvents);

        return SocialAccountOutput::fromEntity($account);
    }
}
