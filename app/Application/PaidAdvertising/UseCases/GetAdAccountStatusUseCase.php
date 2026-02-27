<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\AdAccountOutput;
use App\Application\PaidAdvertising\DTOs\GetAdAccountStatusInput;
use App\Application\PaidAdvertising\Exceptions\AdAccountAuthorizationException;
use App\Domain\PaidAdvertising\Exceptions\AdAccountNotFoundException;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetAdAccountStatusUseCase
{
    public function __construct(
        private readonly AdAccountRepositoryInterface $adAccountRepository,
    ) {}

    public function execute(GetAdAccountStatusInput $input): AdAccountOutput
    {
        $account = $this->adAccountRepository->findById(Uuid::fromString($input->accountId));

        if ($account === null) {
            throw new AdAccountNotFoundException($input->accountId);
        }

        if ((string) $account->organizationId !== $input->organizationId) {
            throw new AdAccountAuthorizationException;
        }

        return AdAccountOutput::fromEntity($account);
    }
}
