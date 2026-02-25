<?php

declare(strict_types=1);

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Entities\Invoice;
use App\Domain\Shared\ValueObjects\Uuid;

interface InvoiceRepositoryInterface
{
    public function findById(Uuid $id): ?Invoice;

    public function findByExternalId(string $externalInvoiceId): ?Invoice;

    public function create(Invoice $invoice): void;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<Invoice>
     */
    public function findByOrganization(
        Uuid $organizationId,
        array $filters = [],
        ?string $cursor = null,
        int $limit = 20,
    ): array;
}
