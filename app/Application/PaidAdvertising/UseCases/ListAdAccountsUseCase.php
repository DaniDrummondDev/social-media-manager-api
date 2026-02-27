<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\DTOs\AdAccountOutput;
use App\Application\PaidAdvertising\DTOs\ListAdAccountsInput;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListAdAccountsUseCase
{
    public function __construct(
        private readonly AdAccountRepositoryInterface $adAccountRepository,
    ) {}

    /**
     * @return array<AdAccountOutput>
     */
    public function execute(ListAdAccountsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        if ($input->provider !== null) {
            $provider = AdProvider::from($input->provider);
            $accounts = $this->adAccountRepository->findByOrganizationAndProvider($organizationId, $provider);
        } else {
            $accounts = $this->adAccountRepository->findByOrganizationId($organizationId);
        }

        return array_map(
            fn ($account) => AdAccountOutput::fromEntity($account),
            $accounts,
        );
    }
}
