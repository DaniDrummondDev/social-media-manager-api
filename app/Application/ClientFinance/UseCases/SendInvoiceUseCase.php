<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\InvoiceOutput;
use App\Application\ClientFinance\DTOs\SendInvoiceInput;
use App\Application\ClientFinance\Exceptions\InvoiceNotFoundException;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class SendInvoiceUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(SendInvoiceInput $input): InvoiceOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $invoiceId = Uuid::fromString($input->invoiceId);

        $invoice = $this->invoiceRepository->findByIdAndOrganization($invoiceId, $organizationId);

        if ($invoice === null) {
            throw new InvoiceNotFoundException();
        }

        $invoice = $invoice->send($input->userId);

        $this->invoiceRepository->update($invoice);

        return InvoiceOutput::fromEntity($invoice);
    }
}
