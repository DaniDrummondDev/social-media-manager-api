<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\CancelInvoiceInput;
use App\Application\ClientFinance\DTOs\InvoiceOutput;
use App\Application\ClientFinance\Exceptions\InvoiceNotFoundException;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class CancelInvoiceUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(CancelInvoiceInput $input): InvoiceOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $invoiceId = Uuid::fromString($input->invoiceId);

        $invoice = $this->invoiceRepository->findByIdAndOrganization($invoiceId, $organizationId);

        if ($invoice === null) {
            throw new InvoiceNotFoundException();
        }

        $invoice = $invoice->cancel();

        $this->invoiceRepository->update($invoice);

        return InvoiceOutput::fromEntity($invoice);
    }
}
