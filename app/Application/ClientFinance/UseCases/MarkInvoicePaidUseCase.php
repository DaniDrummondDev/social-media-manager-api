<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\UseCases;

use App\Application\ClientFinance\DTOs\InvoiceOutput;
use App\Application\ClientFinance\DTOs\MarkInvoicePaidInput;
use App\Application\ClientFinance\Exceptions\InvoiceNotFoundException;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\PaymentMethod;
use App\Domain\Shared\ValueObjects\Uuid;

final class MarkInvoicePaidUseCase
{
    public function __construct(
        private readonly ClientInvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(MarkInvoicePaidInput $input): InvoiceOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $invoiceId = Uuid::fromString($input->invoiceId);

        $invoice = $this->invoiceRepository->findByIdAndOrganization($invoiceId, $organizationId);

        if ($invoice === null) {
            throw new InvoiceNotFoundException();
        }

        $invoice = $invoice->markPaid(
            userId: $input->userId,
            paymentMethod: PaymentMethod::from($input->paymentMethod),
            paymentNotes: $input->paymentNotes,
        );

        $this->invoiceRepository->update($invoice);

        return InvoiceOutput::fromEntity($invoice);
    }
}
