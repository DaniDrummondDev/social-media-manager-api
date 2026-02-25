<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\InvoiceOutput;
use App\Application\ClientFinance\Exceptions\InvoiceNotFoundException;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetInvoiceUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(string $invoiceId, string $organizationId): InvoiceOutput
    {
        $invoice = $this->invoiceRepository->findByIdAndOrganization(
            Uuid::fromString($invoiceId),
            Uuid::fromString($organizationId),
        );

        if ($invoice === null) {
            throw new InvoiceNotFoundException();
        }

        return InvoiceOutput::fromEntity($invoice);
    }
}
