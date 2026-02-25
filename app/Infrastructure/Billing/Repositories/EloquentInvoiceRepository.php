<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Repositories;

use App\Domain\Billing\Entities\Invoice;
use App\Domain\Billing\Repositories\InvoiceRepositoryInterface;
use App\Domain\Billing\ValueObjects\InvoiceStatus;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Billing\Models\InvoiceModel;
use DateTimeImmutable;

final class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        private readonly InvoiceModel $model,
    ) {}

    public function findById(Uuid $id): ?Invoice
    {
        /** @var InvoiceModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByExternalId(string $externalInvoiceId): ?Invoice
    {
        /** @var InvoiceModel|null $record */
        $record = $this->model->newQuery()
            ->where('external_invoice_id', $externalInvoiceId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function create(Invoice $invoice): void
    {
        $this->model->newQuery()->create([
            'id' => (string) $invoice->id,
            'organization_id' => (string) $invoice->organizationId,
            'subscription_id' => (string) $invoice->subscriptionId,
            'external_invoice_id' => $invoice->externalInvoiceId,
            'amount_cents' => $invoice->amount->amountCents,
            'currency' => $invoice->amount->currency,
            'status' => $invoice->status->value,
            'invoice_url' => $invoice->invoiceUrl,
            'period_start' => $invoice->periodStart->format('Y-m-d H:i:s'),
            'period_end' => $invoice->periodEnd->format('Y-m-d H:i:s'),
            'paid_at' => $invoice->paidAt?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<Invoice>
     */
    public function findByOrganization(
        Uuid $organizationId,
        array $filters = [],
        ?string $cursor = null,
        int $limit = 20,
    ): array {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to'].' 23:59:59');
        }

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, InvoiceModel> $records */
        $records = $query->orderByDesc('id')->limit($limit)->get();

        return $records->map(fn (InvoiceModel $r) => $this->toDomain($r))->all();
    }

    private function toDomain(InvoiceModel $model): Invoice
    {
        $periodStart = $model->getAttribute('period_start');
        $periodEnd = $model->getAttribute('period_end');
        $paidAt = $model->getAttribute('paid_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return Invoice::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            subscriptionId: Uuid::fromString($model->getAttribute('subscription_id')),
            externalInvoiceId: $model->getAttribute('external_invoice_id'),
            amount: Money::fromCents((int) $model->getAttribute('amount_cents'), $model->getAttribute('currency') ?? 'BRL'),
            status: InvoiceStatus::from($model->getAttribute('status')),
            invoiceUrl: $model->getAttribute('invoice_url'),
            periodStart: new DateTimeImmutable($periodStart->format('Y-m-d H:i:s')),
            periodEnd: new DateTimeImmutable($periodEnd->format('Y-m-d H:i:s')),
            paidAt: $paidAt ? new DateTimeImmutable($paidAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
