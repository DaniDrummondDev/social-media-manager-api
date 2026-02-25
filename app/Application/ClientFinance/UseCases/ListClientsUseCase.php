<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\ClientOutput;
use App\Application\ClientFinance\DTOs\ListClientsInput;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListClientsUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    /**
     * @return array{items: array<ClientOutput>, next_cursor: ?string}
     */
    public function execute(ListClientsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->clientRepository->findByOrganization(
            organizationId: $organizationId,
            status: $input->status,
            search: $input->search,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($client) => ClientOutput::fromEntity($client),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
