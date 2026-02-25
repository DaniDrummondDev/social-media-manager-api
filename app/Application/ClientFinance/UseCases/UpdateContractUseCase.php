<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\ContractOutput;
use App\Application\ClientFinance\DTOs\UpdateContractInput;
use App\Application\ClientFinance\Exceptions\ContractNotFoundException;
use App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class UpdateContractUseCase
{
    public function __construct(
        private readonly ClientContractRepositoryInterface $contractRepository,
    ) {}

    public function execute(UpdateContractInput $input): ContractOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $contractId = Uuid::fromString($input->contractId);

        $contract = $this->contractRepository->findByIdAndOrganization($contractId, $organizationId);

        if ($contract === null) {
            throw new ContractNotFoundException();
        }

        $contract = $contract->update(
            name: $input->name,
            valueCents: $input->valueCents,
            endsAt: $input->endsAt !== null ? new DateTimeImmutable($input->endsAt) : null,
            socialAccountIds: $input->socialAccountIds,
        );

        $this->contractRepository->update($contract);

        return ContractOutput::fromEntity($contract);
    }
}
