<?php

declare(strict_types=1);

namespace App\Domain\Billing\Entities;

use App\Domain\Billing\Events\PaymentSucceeded;
use App\Domain\Billing\ValueObjects\InvoiceStatus;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Invoice
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $subscriptionId,
        public string $externalInvoiceId,
        public Money $amount,
        public InvoiceStatus $status,
        public ?string $invoiceUrl,
        public DateTimeImmutable $periodStart,
        public DateTimeImmutable $periodEnd,
        public ?DateTimeImmutable $paidAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $subscriptionId,
        string $externalInvoiceId,
        Money $amount,
        InvoiceStatus $status,
        ?string $invoiceUrl,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        ?DateTimeImmutable $paidAt,
        string $userId = 'system',
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        $events = [];
        if ($status === InvoiceStatus::Paid) {
            $events[] = new PaymentSucceeded(
                aggregateId: (string) $id,
                organizationId: (string) $organizationId,
                userId: $userId,
                invoiceId: (string) $id,
                amountCents: $amount->amountCents,
            );
        }

        return new self(
            id: $id,
            organizationId: $organizationId,
            subscriptionId: $subscriptionId,
            externalInvoiceId: $externalInvoiceId,
            amount: $amount,
            status: $status,
            invoiceUrl: $invoiceUrl,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            paidAt: $paidAt,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: $events,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $subscriptionId,
        string $externalInvoiceId,
        Money $amount,
        InvoiceStatus $status,
        ?string $invoiceUrl,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        ?DateTimeImmutable $paidAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            subscriptionId: $subscriptionId,
            externalInvoiceId: $externalInvoiceId,
            amount: $amount,
            status: $status,
            invoiceUrl: $invoiceUrl,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            paidAt: $paidAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @return array<DomainEvent>
     */
    public function releaseEvents(): array
    {
        return $this->domainEvents;
    }
}
