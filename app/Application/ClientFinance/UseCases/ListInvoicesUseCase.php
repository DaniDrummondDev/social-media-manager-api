<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\InvoiceOutput;
use App\Application\ClientFinance\DTOs\ListInvoicesInput;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListInvoicesUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * @return array{items: array<InvoiceOutput>, next_cursor: ?string}
     */
    public function execute(ListInvoicesInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->invoiceRepository->findByOrganization(
            organizationId: $organizationId,
            status: $input->status,
            clientId: $input->clientId,
            referenceMonth: $input->referenceMonth,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($invoice) => InvoiceOutput::fromEntity($invoice),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
