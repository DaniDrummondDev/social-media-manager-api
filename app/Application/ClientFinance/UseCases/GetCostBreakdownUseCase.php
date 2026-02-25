<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\CostAllocationOutput;
use App\Application\ClientFinance\DTOs\CostBreakdownOutput;
use App\Application\ClientFinance\DTOs\GetCostBreakdownInput;
use App\Domain\ClientFinance\Repositories\CostAllocationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetCostBreakdownUseCase
{
    public function __construct(
        private readonly CostAllocationRepositoryInterface $costAllocationRepository,
    ) {}

    public function execute(GetCostBreakdownInput $input): CostBreakdownOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->costAllocationRepository->findByOrganization(
            organizationId: $organizationId,
            clientId: $input->clientId,
            resourceType: $input->resourceType,
            from: $input->from,
            to: $input->to,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($allocation) => CostAllocationOutput::fromEntity($allocation),
            $result['items'],
        );

        return new CostBreakdownOutput(
            items: $items,
            nextCursor: $result['next_cursor'],
        );
    }
}
