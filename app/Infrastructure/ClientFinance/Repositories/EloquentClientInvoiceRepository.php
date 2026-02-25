<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Repositories;

use App\Domain\ClientFinance\Entities\ClientInvoice;
use App\Domain\ClientFinance\Entities\InvoiceItem;
use App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\ClientFinance\ValueObjects\InvoiceStatus;
use App\Domain\ClientFinance\ValueObjects\PaymentMethod;
use App\Domain\ClientFinance\ValueObjects\YearMonth;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ClientFinance\Models\ClientInvoiceItemModel;
use App\Infrastructure\ClientFinance\Models\ClientInvoiceModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentClientInvoiceRepository implements ClientInvoiceRepositoryInterface
{
    public function __construct(
        private readonly ClientInvoiceModel $model,
    ) {}

    public function findById(Uuid $id): ?ClientInvoice
    {
        /** @var ClientInvoiceModel|null $record */
        $record = $this->model->newQuery()->with('items')->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByIdAndOrganization(Uuid $id, Uuid $organizationId): ?ClientInvoice
    {
        /** @var ClientInvoiceModel|null $record */
        $record = $this->model->newQuery()
            ->with('items')
            ->where('id', (string) $id)
            ->where('organization_id', (string) $organizationId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<ClientInvoice>, next_cursor: ?string}
     */
    public function findByClient(
        Uuid $clientId,
        Uuid $organizationId,
        ?string $status = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array {
        $query = $this->model->newQuery()
            ->with('items')
            ->where('client_id', (string) $clientId)
            ->where('organization_id', (string) $organizationId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($cursor !== null) {
            $query->where('id', '>', $cursor);
        }

        $query->orderBy('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ClientInvoiceModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (ClientInvoiceModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

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
    ): array {
        $query = $this->model->newQuery()
            ->with('items')
            ->where('organization_id', (string) $organizationId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($clientId !== null) {
            $query->where('client_id', $clientId);
        }

        if ($referenceMonth !== null) {
            $query->where('reference_month', $referenceMonth);
        }

        if ($cursor !== null) {
            $query->where('id', '>', $cursor);
        }

        $query->orderBy('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ClientInvoiceModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (ClientInvoiceModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    public function findByContractAndMonth(Uuid $contractId, YearMonth $month): ?ClientInvoice
    {
        /** @var ClientInvoiceModel|null $record */
        $record = $this->model->newQuery()
            ->with('items')
            ->where('contract_id', (string) $contractId)
            ->where('reference_month', $month->toString())
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<ClientInvoice>
     */
    public function findSentOverdue(DateTimeImmutable $now): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ClientInvoiceModel> $records */
        $records = $this->model->newQuery()
            ->with('items')
            ->where('status', 'sent')
            ->where('due_date', '<', $now->format('Y-m-d'))
            ->get();

        return $records->map(fn (ClientInvoiceModel $r) => $this->toDomain($r))->all();
    }

    public function create(ClientInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $this->model->newQuery()->create($this->toArray($invoice));

            foreach ($invoice->items as $item) {
                (new ClientInvoiceItemModel)->newQuery()->create([
                    'id' => (string) $item->id,
                    'client_invoice_id' => (string) $invoice->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unitPriceCents,
                    'total_cents' => $item->totalCents,
                    'position' => $item->position,
                ]);
            }
        });
    }

    public function update(ClientInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $this->model->newQuery()
                ->where('id', (string) $invoice->id)
                ->update($this->toArray($invoice));

            (new ClientInvoiceItemModel)->newQuery()
                ->where('client_invoice_id', (string) $invoice->id)
                ->delete();

            foreach ($invoice->items as $item) {
                (new ClientInvoiceItemModel)->newQuery()->create([
                    'id' => (string) $item->id,
                    'client_invoice_id' => (string) $invoice->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unitPriceCents,
                    'total_cents' => $item->totalCents,
                    'position' => $item->position,
                ]);
            }
        });
    }

    public function sumPaidByClient(Uuid $clientId, Uuid $organizationId, ?YearMonth $month = null): int
    {
        $query = $this->model->newQuery()
            ->where('client_id', (string) $clientId)
            ->where('organization_id', (string) $organizationId)
            ->where('status', 'paid');

        if ($month !== null) {
            $query->where('reference_month', $month->toString());
        }

        return (int) $query->sum('total_cents');
    }

    public function sumPaidByOrganization(Uuid $organizationId, ?YearMonth $month = null): int
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('status', 'paid');

        if ($month !== null) {
            $query->where('reference_month', $month->toString());
        }

        return (int) $query->sum('total_cents');
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ClientInvoice $invoice): array
    {
        return [
            'id' => (string) $invoice->id,
            'client_id' => (string) $invoice->clientId,
            'contract_id' => $invoice->contractId ? (string) $invoice->contractId : null,
            'organization_id' => (string) $invoice->organizationId,
            'reference_month' => $invoice->referenceMonth->toString(),
            'subtotal_cents' => $invoice->subtotalCents,
            'discount_cents' => $invoice->discountCents,
            'total_cents' => $invoice->totalCents,
            'currency' => $invoice->currency->value,
            'status' => $invoice->status->value,
            'due_date' => $invoice->dueDate->format('Y-m-d'),
            'paid_at' => $invoice->paidAt?->format('Y-m-d H:i:s'),
            'sent_at' => $invoice->sentAt?->format('Y-m-d H:i:s'),
            'payment_method' => $invoice->paymentMethod?->value,
            'payment_notes' => $invoice->paymentNotes,
            'notes' => $invoice->notes,
        ];
    }

    private function toDomain(ClientInvoiceModel $model): ClientInvoice
    {
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');
        $dueDate = $model->getAttribute('due_date');
        $paidAt = $model->getAttribute('paid_at');
        $sentAt = $model->getAttribute('sent_at');
        $contractId = $model->getAttribute('contract_id');
        $paymentMethod = $model->getAttribute('payment_method');

        /** @var \Illuminate\Database\Eloquent\Collection<int, ClientInvoiceItemModel> $itemModels */
        $itemModels = $model->getRelation('items');

        $items = $itemModels->map(fn (ClientInvoiceItemModel $item) => InvoiceItem::reconstitute(
            id: Uuid::fromString($item->getAttribute('id')),
            description: $item->getAttribute('description'),
            quantity: (int) $item->getAttribute('quantity'),
            unitPriceCents: (int) $item->getAttribute('unit_price_cents'),
            totalCents: (int) $item->getAttribute('total_cents'),
            position: (int) $item->getAttribute('position'),
        ))->all();

        return ClientInvoice::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            clientId: Uuid::fromString($model->getAttribute('client_id')),
            contractId: $contractId !== null ? Uuid::fromString($contractId) : null,
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            referenceMonth: YearMonth::fromString($model->getAttribute('reference_month')),
            items: $items,
            subtotalCents: (int) $model->getAttribute('subtotal_cents'),
            discountCents: (int) $model->getAttribute('discount_cents'),
            totalCents: (int) $model->getAttribute('total_cents'),
            currency: Currency::from($model->getAttribute('currency')),
            status: InvoiceStatus::from($model->getAttribute('status')),
            dueDate: new DateTimeImmutable($dueDate->format('Y-m-d')),
            paidAt: $paidAt ? new DateTimeImmutable($paidAt->format('Y-m-d H:i:s')) : null,
            sentAt: $sentAt ? new DateTimeImmutable($sentAt->format('Y-m-d H:i:s')) : null,
            paymentMethod: $paymentMethod !== null ? PaymentMethod::from($paymentMethod) : null,
            paymentNotes: $model->getAttribute('payment_notes'),
            notes: $model->getAttribute('notes'),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
