<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\ContractOutput;
use App\Application\ClientFinance\DTOs\ListContractsInput;
use App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListContractsUseCase
{
    public function __construct(
        private readonly ClientContractRepositoryInterface $contractRepository,
    ) {}

    /**
     * @return array{items: array<ContractOutput>, next_cursor: ?string}
     */
    public function execute(ListContractsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $clientId = Uuid::fromString($input->clientId);

        $result = $this->contractRepository->findByClient(
            clientId: $clientId,
            organizationId: $organizationId,
            status: $input->status,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($contract) => ContractOutput::fromEntity($contract),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
