<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Repositories;

use App\Domain\ClientFinance\Entities\ClientInvoice;
use App\Domain\ClientFinance\ValueObjects\YearMonth;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface ClientInvoiceRepositoryInterface
{
    public function findById(Uuid $id): ?ClientInvoice;

    public function findByIdAndOrganization(Uuid $id, Uuid $organizationId): ?ClientInvoice;

    /**
     * @return array{items: array<ClientInvoice>, next_cursor: ?string}
     */
    public function findByClient(
        Uuid $clientId,
        Uuid $organizationId,
        ?string $status = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array;

    /**
     * @return array{items: array<ClientInvoice>, next_cursor: ?string}
     */
    public function findByOrganization(
        Uuid $organizationId,
        ?string $status = null,
        ?string $clientId = null,
        ?string $referenceMonth = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array;

    public function findByContractAndMonth(Uuid $contractId, YearMonth $month): ?ClientInvoice;

    /**
     * @return array<ClientInvoice>
     */
    public function findSentOverdue(DateTimeImmutable $now): array;

    public function create(ClientInvoice $invoice): void;

    public function update(ClientInvoice $invoice): void;

    public function sumPaidByClient(Uuid $clientId, Uuid $organizationId, ?YearMonth $month = null): int;

    public function sumPaidByOrganization(Uuid $organizationId, ?YearMonth $month = null): int;
}
