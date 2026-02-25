<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Entities;

use App\Domain\ClientFinance\Events\InvoiceGenerated;
use App\Domain\ClientFinance\Events\InvoiceMarkedPaid;
use App\Domain\ClientFinance\Events\InvoiceOverdue;
use App\Domain\ClientFinance\Events\InvoiceSent;
use App\Domain\ClientFinance\Exceptions\InvalidInvoiceTransitionException;
use App\Domain\ClientFinance\Exceptions\InvoiceNotEditableException;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\ClientFinance\ValueObjects\InvoiceStatus;
use App\Domain\ClientFinance\ValueObjects\PaymentMethod;
use App\Domain\ClientFinance\ValueObjects\YearMonth;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class ClientInvoice
{
    /**
     * @param  array<InvoiceItem>  $items
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $clientId,
        public ?Uuid $contractId,
        public Uuid $organizationId,
        public YearMonth $referenceMonth,
        public array $items,
        public int $subtotalCents,
        public int $discountCents,
        public int $totalCents,
        public Currency $currency,
        public InvoiceStatus $status,
        public DateTimeImmutable $dueDate,
        public ?DateTimeImmutable $paidAt,
        public ?DateTimeImmutable $sentAt,
        public ?PaymentMethod $paymentMethod,
        public ?string $paymentNotes,
        public ?string $notes,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<InvoiceItem>  $items
     */
    public static function create(
        Uuid $clientId,
        ?Uuid $contractId,
        Uuid $organizationId,
        YearMonth $referenceMonth,
        array $items,
        int $discountCents,
        Currency $currency,
        DateTimeImmutable $dueDate,
        ?string $notes,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        $subtotalCents = self::calculateSubtotal($items);
        $totalCents = max(0, $subtotalCents - $discountCents);

        return new self(
            id: $id,
            clientId: $clientId,
            contractId: $contractId,
            organizationId: $organizationId,
            referenceMonth: $referenceMonth,
            items: $items,
            subtotalCents: $subtotalCents,
            discountCents: $discountCents,
            totalCents: $totalCents,
            currency: $currency,
            status: InvoiceStatus::Draft,
            dueDate: $dueDate,
            paidAt: null,
            sentAt: null,
            paymentMethod: null,
            paymentNotes: null,
            notes: $notes,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new InvoiceGenerated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    clientId: (string) $clientId,
                    totalCents: $totalCents,
                ),
            ],
        );
    }

    /**
     * @param  array<InvoiceItem>  $items
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $clientId,
        ?Uuid $contractId,
        Uuid $organizationId,
        YearMonth $referenceMonth,
        array $items,
        int $subtotalCents,
        int $discountCents,
        int $totalCents,
        Currency $currency,
        InvoiceStatus $status,
        DateTimeImmutable $dueDate,
        ?DateTimeImmutable $paidAt,
        ?DateTimeImmutable $sentAt,
        ?PaymentMethod $paymentMethod,
        ?string $paymentNotes,
        ?string $notes,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            clientId: $clientId,
            contractId: $contractId,
            organizationId: $organizationId,
            referenceMonth: $referenceMonth,
            items: $items,
            subtotalCents: $subtotalCents,
            discountCents: $discountCents,
            totalCents: $totalCents,
            currency: $currency,
            status: $status,
            dueDate: $dueDate,
            paidAt: $paidAt,
            sentAt: $sentAt,
            paymentMethod: $paymentMethod,
            paymentNotes: $paymentNotes,
            notes: $notes,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function send(string $userId): self
    {
        $this->ensureTransition(InvoiceStatus::Sent);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            contractId: $this->contractId,
            organizationId: $this->organizationId,
            referenceMonth: $this->referenceMonth,
            items: $this->items,
            subtotalCents: $this->subtotalCents,
            discountCents: $this->discountCents,
            totalCents: $this->totalCents,
            currency: $this->currency,
            status: InvoiceStatus::Sent,
            dueDate: $this->dueDate,
            paidAt: null,
            sentAt: $now,
            paymentMethod: $this->paymentMethod,
            paymentNotes: $this->paymentNotes,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new InvoiceSent(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    clientId: (string) $this->clientId,
                ),
            ],
        );
    }

    public function markPaid(string $userId, PaymentMethod $paymentMethod, ?string $paymentNotes = null): self
    {
        $this->ensureTransition(InvoiceStatus::Paid);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            contractId: $this->contractId,
            organizationId: $this->organizationId,
            referenceMonth: $this->referenceMonth,
            items: $this->items,
            subtotalCents: $this->subtotalCents,
            discountCents: $this->discountCents,
            totalCents: $this->totalCents,
            currency: $this->currency,
            status: InvoiceStatus::Paid,
            dueDate: $this->dueDate,
            paidAt: $now,
            sentAt: $this->sentAt,
            paymentMethod: $paymentMethod,
            paymentNotes: $paymentNotes,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                new InvoiceMarkedPaid(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    clientId: (string) $this->clientId,
                    totalCents: $this->totalCents,
                ),
            ],
        );
    }

    public function markOverdue(string $userId): self
    {
        $this->ensureTransition(InvoiceStatus::Overdue);

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            contractId: $this->contractId,
            organizationId: $this->organizationId,
            referenceMonth: $this->referenceMonth,
            items: $this->items,
            subtotalCents: $this->subtotalCents,
            discountCents: $this->discountCents,
            totalCents: $this->totalCents,
            currency: $this->currency,
            status: InvoiceStatus::Overdue,
            dueDate: $this->dueDate,
            paidAt: null,
            sentAt: $this->sentAt,
            paymentMethod: $this->paymentMethod,
            paymentNotes: $this->paymentNotes,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                new InvoiceOverdue(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    clientId: (string) $this->clientId,
                ),
            ],
        );
    }

    public function cancel(): self
    {
        $this->ensureTransition(InvoiceStatus::Cancelled);

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            contractId: $this->contractId,
            organizationId: $this->organizationId,
            referenceMonth: $this->referenceMonth,
            items: $this->items,
            subtotalCents: $this->subtotalCents,
            discountCents: $this->discountCents,
            totalCents: $this->totalCents,
            currency: $this->currency,
            status: InvoiceStatus::Cancelled,
            dueDate: $this->dueDate,
            paidAt: null,
            sentAt: $this->sentAt,
            paymentMethod: $this->paymentMethod,
            paymentNotes: $this->paymentNotes,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<InvoiceItem>  $items
     */
    public function updateDraft(
        array $items,
        int $discountCents,
        ?string $notes,
        ?DateTimeImmutable $dueDate = null,
    ): self {
        if (! $this->status->isEditable()) {
            throw new InvoiceNotEditableException;
        }

        $subtotalCents = self::calculateSubtotal($items);
        $totalCents = max(0, $subtotalCents - $discountCents);

        return new self(
            id: $this->id,
            clientId: $this->clientId,
            contractId: $this->contractId,
            organizationId: $this->organizationId,
            referenceMonth: $this->referenceMonth,
            items: $items,
            subtotalCents: $subtotalCents,
            discountCents: $discountCents,
            totalCents: $totalCents,
            currency: $this->currency,
            status: $this->status,
            dueDate: $dueDate ?? $this->dueDate,
            paidAt: null,
            sentAt: null,
            paymentMethod: null,
            paymentNotes: null,
            notes: $notes ?? $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    /**
     * @return array<DomainEvent>
     */
    public function releaseEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * @param  array<InvoiceItem>  $items
     */
    private static function calculateSubtotal(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item->totalCents;
        }

        return $total;
    }

    private function ensureTransition(InvoiceStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidInvoiceTransitionException(
                "Não é possível transicionar de '{$this->status->value}' para '{$target->value}'.",
            );
        }
    }
}
