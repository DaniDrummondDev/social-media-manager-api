<?php

declare(strict_types=1);

use App\Domain\Billing\Entities\Invoice;
use App\Domain\Billing\Events\PaymentSucceeded;
use App\Domain\Billing\ValueObjects\InvoiceStatus;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Shared\ValueObjects\Uuid;

describe('create', function () {
    it('dispatches PaymentSucceeded event when status is Paid', function () {
        $orgId = Uuid::generate();
        $subscriptionId = Uuid::generate();
        $now = new DateTimeImmutable;

        $invoice = Invoice::create(
            organizationId: $orgId,
            subscriptionId: $subscriptionId,
            externalInvoiceId: 'in_stripe_123',
            amount: Money::fromCents(4900),
            status: InvoiceStatus::Paid,
            invoiceUrl: 'https://stripe.com/invoice/123',
            periodStart: $now,
            periodEnd: $now->modify('+30 days'),
            paidAt: $now,
            userId: 'user-456',
        );

        expect($invoice->organizationId->equals($orgId))->toBeTrue()
            ->and($invoice->subscriptionId->equals($subscriptionId))->toBeTrue()
            ->and($invoice->externalInvoiceId)->toBe('in_stripe_123')
            ->and($invoice->amount->amountCents)->toBe(4900)
            ->and($invoice->status)->toBe(InvoiceStatus::Paid)
            ->and($invoice->invoiceUrl)->toBe('https://stripe.com/invoice/123')
            ->and($invoice->paidAt)->toBe($now)
            ->and($invoice->domainEvents)->toHaveCount(1)
            ->and($invoice->domainEvents[0])->toBeInstanceOf(PaymentSucceeded::class)
            ->and($invoice->domainEvents[0]->amountCents)->toBe(4900);
    });

    it('does NOT dispatch events when status is Open', function () {
        $now = new DateTimeImmutable;

        $invoice = Invoice::create(
            organizationId: Uuid::generate(),
            subscriptionId: Uuid::generate(),
            externalInvoiceId: 'in_stripe_456',
            amount: Money::fromCents(4900),
            status: InvoiceStatus::Open,
            invoiceUrl: 'https://stripe.com/invoice/456',
            periodStart: $now,
            periodEnd: $now->modify('+30 days'),
            paidAt: null,
        );

        expect($invoice->status)->toBe(InvoiceStatus::Open)
            ->and($invoice->domainEvents)->toBeEmpty();
    });
});

describe('reconstitute', function () {
    it('does NOT dispatch events', function () {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        $invoice = Invoice::reconstitute(
            id: $id,
            organizationId: Uuid::generate(),
            subscriptionId: Uuid::generate(),
            externalInvoiceId: 'in_stripe_789',
            amount: Money::fromCents(4900),
            status: InvoiceStatus::Paid,
            invoiceUrl: 'https://stripe.com/invoice/789',
            periodStart: $now,
            periodEnd: $now->modify('+30 days'),
            paidAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );

        expect($invoice->id->equals($id))->toBeTrue()
            ->and($invoice->status)->toBe(InvoiceStatus::Paid)
            ->and($invoice->domainEvents)->toBeEmpty();
    });
});
