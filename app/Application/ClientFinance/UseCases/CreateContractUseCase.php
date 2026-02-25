<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\ContractOutput;
use App\Application\ClientFinance\DTOs\CreateContractInput;
use App\Application\ClientFinance\Exceptions\ClientNotFoundException;
use App\Domain\ClientFinance\Entities\ClientContract;
use App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\ContractType;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class CreateContractUseCase
{
    public function __construct(
        private readonly ClientContractRepositoryInterface $contractRepository,
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(CreateContractInput $input): ContractOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $clientId = Uuid::fromString($input->clientId);

        $client = $this->clientRepository->findByIdAndOrganization($clientId, $organizationId);

        if ($client === null) {
            throw new ClientNotFoundException();
        }

        $contract = ClientContract::create(
            clientId: $clientId,
            organizationId: $organizationId,
            name: $input->name,
            type: ContractType::from($input->type),
            valueCents: $input->valueCents,
            currency: Currency::from($input->currency),
            startsAt: new DateTimeImmutable($input->startsAt),
            endsAt: $input->endsAt !== null ? new DateTimeImmutable($input->endsAt) : null,
            socialAccountIds: $input->socialAccountIds,
            userId: $input->userId,
        );

        $this->contractRepository->create($contract);

        return ContractOutput::fromEntity($contract);
    }
}
