<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\Contracts\AdOAuthStateServiceInterface;
use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\AdAccountOutput;
use App\Application\PaidAdvertising\DTOs\HandleAdAccountCallbackInput;
use App\Application\PaidAdvertising\Exceptions\AdOAuthStateInvalidException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class HandleAdAccountCallbackUseCase
{
    public function __construct(
        private readonly AdOAuthStateServiceInterface $stateService,
        private readonly AdPlatformFactoryInterface $platformFactory,
        private readonly AdTokenEncryptorInterface $tokenEncryptor,
        private readonly AdAccountRepositoryInterface $adAccountRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(HandleAdAccountCallbackInput $input): AdAccountOutput
    {
        $stateData = $this->stateService->validateAndConsumeState($input->state);

        if ($stateData === null) {
            throw new AdOAuthStateInvalidException;
        }

        $organizationId = Uuid::fromString($stateData['organizationId']);
        $userId = Uuid::fromString($stateData['userId']);
        $provider = AdProvider::from($stateData['provider']);

        $adapter = $this->platformFactory->make($provider);
        $tokenData = $adapter->handleCallback($input->code, $input->state);

        $encryptedAccessToken = $this->tokenEncryptor->encrypt($tokenData['access_token']);
        $encryptedRefreshToken = $tokenData['refresh_token'] !== null
            ? $this->tokenEncryptor->encrypt($tokenData['refresh_token'])
            : null;

        $credentials = AdAccountCredentials::create(
            encryptedAccessToken: $encryptedAccessToken,
            encryptedRefreshToken: $encryptedRefreshToken,
            expiresAt: $tokenData['expires_at'] !== null ? new DateTimeImmutable($tokenData['expires_at']) : null,
            scopes: $tokenData['scopes'],
        );

        $existing = $this->adAccountRepository->findByProviderAndProviderAccountId(
            $provider,
            $tokenData['account_id'],
        );

        if ($existing !== null) {
            if ((string) $existing->organizationId !== (string) $organizationId) {
                throw new AdAccountNotFoundException($tokenData['account_id']);
            }

            $updated = $existing->refreshCredentials($credentials);
            $this->adAccountRepository->update($updated);
            $this->eventDispatcher->dispatch(...$updated->domainEvents);

            return AdAccountOutput::fromEntity($updated);
        }

        $account = AdAccount::create(
            organizationId: $organizationId,
            connectedBy: $userId,
            provider: $provider,
            providerAccountId: $tokenData['account_id'],
            providerAccountName: $tokenData['account_name'],
            credentials: $credentials,
        );

        $this->adAccountRepository->create($account);
        $this->eventDispatcher->dispatch(...$account->domainEvents);

        return AdAccountOutput::fromEntity($account);
    }
}
