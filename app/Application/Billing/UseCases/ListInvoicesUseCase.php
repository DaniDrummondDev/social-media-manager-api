<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\InvoiceOutput;
use App\Application\Billing\DTOs\ListInvoicesInput;
use App\Domain\Billing\Repositories\InvoiceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListInvoicesUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * @return array<InvoiceOutput>
     */
    public function execute(ListInvoicesInput $input): array
    {
        $filters = [];

        if ($input->status !== null) {
            $filters['status'] = $input->status;
        }
        if ($input->from !== null) {
            $filters['from'] = $input->from;
        }
        if ($input->to !== null) {
            $filters['to'] = $input->to;
        }

        $invoices = $this->invoiceRepository->findByOrganization(
            Uuid::fromString($input->organizationId),
            $filters,
            $input->cursor,
            $input->limit,
        );

        return array_map(
            fn ($invoice) => InvoiceOutput::fromEntity($invoice),
            $invoices,
        );
    }
}
