<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\ContractOutput;
use App\Application\ClientFinance\Exceptions\ContractNotFoundException;
use App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class PauseContractUseCase
{
    public function __construct(
        private readonly ClientContractRepositoryInterface $contractRepository,
    ) {}

    public function execute(string $contractId, string $organizationId): ContractOutput
    {
        $contract = $this->contractRepository->findByIdAndOrganization(
            Uuid::fromString($contractId),
            Uuid::fromString($organizationId),
        );

        if ($contract === null) {
            throw new ContractNotFoundException();
        }

        $contract = $contract->pause();

        $this->contractRepository->update($contract);

        return ContractOutput::fromEntity($contract);
    }
}
