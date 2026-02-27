<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\Contracts\AdPlatformFactoryInterface;
use App\Application\PaidAdvertising\Contracts\AdTokenEncryptorInterface;
use App\Application\PaidAdvertising\DTOs\SearchInterestsInput;
use App\Application\PaidAdvertising\DTOs\SearchInterestsOutput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Application\PaidAdvertising\Exceptions\AdAccountNotOperationalException;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class SearchInterestsUseCase
{
    public function __construct(
        private readonly AdAccountRepositoryInterface $adAccountRepository,
        private readonly AdPlatformFactoryInterface $platformFactory,
        private readonly AdTokenEncryptorInterface $tokenEncryptor,
    ) {}

    public function execute(SearchInterestsInput $input): SearchInterestsOutput
    {
        $account = $this->adAccountRepository->findById(Uuid::fromString($input->accountId));

        if ($account === null) {
            throw new AdAccountNotFoundException($input->accountId);
        }

        if ((string) $account->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        if (! $account->isOperational()) {
            throw new AdAccountNotOperationalException($input->accountId, $account->status->value);
        }

        $adapter = $this->platformFactory->make($account->provider);
        $decryptedToken = $this->tokenEncryptor->decrypt($account->credentials->encryptedAccessToken);

        $interests = $adapter->searchInterests($decryptedToken, $input->query, $input->limit);

        return new SearchInterestsOutput(interests: $interests);
    }
}
