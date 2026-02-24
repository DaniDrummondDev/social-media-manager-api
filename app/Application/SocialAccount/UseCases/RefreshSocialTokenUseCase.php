<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Application\SocialAccount\DTOs\RefreshSocialTokenInput;
use App\Application\SocialAccount\DTOs\SocialAccountOutput;
use App\Application\SocialAccount\Exceptions\SocialAccountAuthorizationException;
use App\Application\SocialAccount\Exceptions\SocialAccountNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;

final class RefreshSocialTokenUseCase
{
    public function __construct(
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
        private readonly SocialAccountAdapterFactoryInterface $adapterFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(RefreshSocialTokenInput $input): SocialAccountOutput
    {
        $account = $this->socialAccountRepository->findById(Uuid::fromString($input->accountId));

        if ($account === null) {
            throw new SocialAccountNotFoundException($input->accountId);
        }

        if ((string) $account->organizationId !== $input->organizationId) {
            throw new SocialAccountAuthorizationException;
        }

        try {
            $adapter = $this->adapterFactory->make($account->provider);
            $newCredentials = $adapter->refreshToken($account->credentials->accessToken);

            $refreshed = $account->refreshToken($newCredentials);

            $this->socialAccountRepository->update($refreshed);
            $this->eventDispatcher->dispatch(...$refreshed->domainEvents);

            return SocialAccountOutput::fromEntity($refreshed);
        } catch (\Throwable) {
            $expired = $account->markTokenExpired();

            $this->socialAccountRepository->update($expired);
            $this->eventDispatcher->dispatch(...$expired->domainEvents);

            return SocialAccountOutput::fromEntity($expired);
        }
    }
}
