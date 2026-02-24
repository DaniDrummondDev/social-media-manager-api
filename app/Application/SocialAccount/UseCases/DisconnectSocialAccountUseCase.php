<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialAccount\Contracts\SocialAccountAdapterFactoryInterface;
use App\Application\SocialAccount\DTOs\DisconnectSocialAccountInput;
use App\Application\SocialAccount\Exceptions\SocialAccountAuthorizationException;
use App\Application\SocialAccount\Exceptions\SocialAccountNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;

final class DisconnectSocialAccountUseCase
{
    public function __construct(
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
        private readonly SocialAccountAdapterFactoryInterface $adapterFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DisconnectSocialAccountInput $input): void
    {
        $account = $this->socialAccountRepository->findById(Uuid::fromString($input->accountId));

        if ($account === null) {
            throw new SocialAccountNotFoundException($input->accountId);
        }

        if ((string) $account->organizationId !== $input->organizationId) {
            throw new SocialAccountAuthorizationException;
        }

        $disconnected = $account->disconnect($input->userId);

        $this->socialAccountRepository->update($disconnected);
        $this->eventDispatcher->dispatch(...$disconnected->domainEvents);

        try {
            $adapter = $this->adapterFactory->make($account->provider);
            $adapter->revokeToken($account->credentials->accessToken);
        } catch (\Throwable) {
            // Best-effort revocation — do not block disconnect
        }
    }
}
