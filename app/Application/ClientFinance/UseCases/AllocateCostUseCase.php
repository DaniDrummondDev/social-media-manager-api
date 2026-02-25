<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\AllocateCostInput;
use App\Application\ClientFinance\DTOs\CostAllocationOutput;
use App\Application\ClientFinance\Exceptions\ClientNotFoundException;
use App\Domain\ClientFinance\Entities\CostAllocation;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\ClientFinance\Repositories\CostAllocationRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\ClientFinance\ValueObjects\ResourceType;
use App\Domain\Shared\ValueObjects\Uuid;

final class AllocateCostUseCase
{
    public function __construct(
        private readonly CostAllocationRepositoryInterface $costAllocationRepository,
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    public function execute(AllocateCostInput $input): CostAllocationOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $clientId = Uuid::fromString($input->clientId);

        $client = $this->clientRepository->findByIdAndOrganization($clientId, $organizationId);

        if ($client === null) {
            throw new ClientNotFoundException();
        }

        $resourceId = $input->resourceId !== null
            ? Uuid::fromString($input->resourceId)
            : null;

        $allocation = CostAllocation::create(
            clientId: $clientId,
            organizationId: $organizationId,
            resourceType: ResourceType::from($input->resourceType),
            resourceId: $resourceId,
            description: $input->description,
            costCents: $input->costCents,
            currency: Currency::from($input->currency),
            userId: $input->userId,
        );

        $this->costAllocationRepository->create($allocation);

        return CostAllocationOutput::fromEntity($allocation);
    }
}
