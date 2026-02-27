<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\TestAdAccountConnectionInput;
use App\Application\PaidAdvertising\DTOs\TestAdAccountConnectionOutput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class TestAdAccountConnectionUseCase
{
    public function __construct(
        private readonly AdAccountRepositoryInterface $adAccountRepository,
        private readonly AdPlatformFactoryInterface $platformFactory,
        private readonly AdTokenEncryptorInterface $tokenEncryptor,
    ) {}

    public function execute(TestAdAccountConnectionInput $input): TestAdAccountConnectionOutput
    {
        $account = $this->adAccountRepository->findById(Uuid::fromString($input->accountId));

        if ($account === null) {
            throw new AdAccountNotFoundException($input->accountId);
        }

        if ((string) $account->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        try {
            $adapter = $this->platformFactory->make($account->provider);
            $decryptedToken = $this->tokenEncryptor->decrypt($account->credentials->encryptedAccessToken);

            $adapter->searchInterests($decryptedToken, '', 1);

            return new TestAdAccountConnectionOutput(
                isConnected: true,
                providerAccountName: $account->providerAccountName,
            );
        } catch (\Throwable $e) {
            return new TestAdAccountConnectionOutput(
                isConnected: false,
                providerAccountName: $account->providerAccountName,
                error: $e->getMessage(),
            );
        }
    }
}
