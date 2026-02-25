<?php

declare(strict_types=1);

use App\Domain\ClientFinance\Entities\ClientInvoice;
use App\Domain\ClientFinance\Entities\InvoiceItem;
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
use App\Domain\Shared\ValueObjects\Uuid;

function createInvoiceItems(): array
{
    return [
        InvoiceItem::create(description: 'Gestão de Redes Sociais', quantity: 1, unitPriceCents: 300000, position: 1),
        InvoiceItem::create(description: 'Criação de Conteúdo', quantity: 5, unitPriceCents: 50000, position: 2),
    ];
}

function createDraftInvoice(): ClientInvoice
{
    return ClientInvoice::create(
        clientId: Uuid::generate(),
        contractId: Uuid::generate(),
        organizationId: Uuid::generate(),
        referenceMonth: YearMonth::fromString('2026-02'),
        items: createInvoiceItems(),
        discountCents: 10000,
        currency: Currency::BRL,
        dueDate: new DateTimeImmutable('2026-03-10'),
        notes: 'Fatura mensal',
        userId: (string) Uuid::generate(),
    );
}

function createSentInvoice(): ClientInvoice
{
    $items = createInvoiceItems();
    $subtotal = 300000 + (5 * 50000); // 550000
    $discount = 10000;
    $total = $subtotal - $discount; // 540000

    return ClientInvoice::reconstitute(
        id: Uuid::generate(),
        clientId: Uuid::generate(),
        contractId: Uuid::generate(),
        organizationId: Uuid::generate(),
        referenceMonth: YearMonth::fromString('2026-02'),
        items: $items,
        subtotalCents: $subtotal,
        discountCents: $discount,
        totalCents: $total,
        currency: Currency::BRL,
        status: InvoiceStatus::Sent,
        dueDate: new DateTimeImmutable('2026-03-10'),
        paidAt: null,
        sentAt: new DateTimeImmutable,
        paymentMethod: null,
        paymentNotes: null,
        notes: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('calculates totals correctly and emits InvoiceGenerated on create', function () {
    $invoice = createDraftInvoice();

    // Item 1: 1 * 300000 = 300000
    // Item 2: 5 * 50000  = 250000
    // Subtotal: 550000, Discount: 10000, Total: 540000
    $events = $invoice->releaseEvents();

    expect($invoice->subtotalCents)->toBe(550000)
        ->and($invoice->discountCents)->toBe(10000)
        ->and($invoice->totalCents)->toBe(540000)
        ->and($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(InvoiceGenerated::class)
        ->and($events[0]->totalCents)->toBe(540000);
});

it('emits InvoiceSent and sets sentAt on send', function () {
    $invoice = createDraftInvoice();
    $sent = $invoice->send(userId: (string) Uuid::generate());

    $events = $sent->releaseEvents();

    expect($sent->status)->toBe(InvoiceStatus::Sent)
        ->and($sent->sentAt)->not->toBeNull()
        ->and($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(InvoiceSent::class);
});

it('emits InvoiceMarkedPaid and sets paidAt/paymentMethod on markPaid', function () {
    $invoice = createSentInvoice();
    $paid = $invoice->markPaid(
        userId: (string) Uuid::generate(),
        paymentMethod: PaymentMethod::Pix,
        paymentNotes: 'Pago via Pix',
    );

    $events = $paid->releaseEvents();

    expect($paid->status)->toBe(InvoiceStatus::Paid)
        ->and($paid->paidAt)->not->toBeNull()
        ->and($paid->paymentMethod)->toBe(PaymentMethod::Pix)
        ->and($paid->paymentNotes)->toBe('Pago via Pix')
        ->and($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(InvoiceMarkedPaid::class)
        ->and($events[0]->totalCents)->toBe($paid->totalCents);
});

it('emits InvoiceOverdue on markOverdue', function () {
    $invoice = createSentInvoice();
    $overdue = $invoice->markOverdue(userId: (string) Uuid::generate());

    $events = $overdue->releaseEvents();

    expect($overdue->status)->toBe(InvoiceStatus::Overdue)
        ->and($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(InvoiceOverdue::class);
});

it('cancels from draft status', function () {
    $invoice = createDraftInvoice();
    $cancelled = $invoice->cancel();

    expect($cancelled->status)->toBe(InvoiceStatus::Cancelled);
});

it('cancels from sent status', function () {
    $invoice = createSentInvoice();
    $cancelled = $invoice->cancel();

    expect($cancelled->status)->toBe(InvoiceStatus::Cancelled);
});

it('updates draft with new items and recalculates totals', function () {
    $invoice = createDraftInvoice();

    $newItems = [
        InvoiceItem::create(description: 'Serviço Novo', quantity: 2, unitPriceCents: 100000, position: 1),
    ];

    $updated = $invoice->updateDraft(
        items: $newItems,
        discountCents: 5000,
        notes: 'Nota atualizada',
    );

    // Item: 2 * 100000 = 200000
    // Subtotal: 200000, Discount: 5000, Total: 195000
    expect($updated->subtotalCents)->toBe(200000)
        ->and($updated->discountCents)->toBe(5000)
        ->and($updated->totalCents)->toBe(195000)
        ->and($updated->notes)->toBe('Nota atualizada')
        ->and($updated->items)->toHaveCount(1);
});

it('throws InvoiceNotEditableException when updating non-draft invoice', function () {
    $invoice = createSentInvoice();

    expect(fn () => $invoice->updateDraft(
        items: createInvoiceItems(),
        discountCents: 0,
        notes: null,
    ))->toThrow(InvoiceNotEditableException::class);
});

it('throws InvalidInvoiceTransitionException for paid -> sent', function () {
    $items = createInvoiceItems();

    $invoice = ClientInvoice::reconstitute(
        id: Uuid::generate(),
        clientId: Uuid::generate(),
        contractId: null,
        organizationId: Uuid::generate(),
        referenceMonth: YearMonth::fromString('2026-02'),
        items: $items,
        subtotalCents: 550000,
        discountCents: 0,
        totalCents: 550000,
        currency: Currency::BRL,
        status: InvoiceStatus::Paid,
        dueDate: new DateTimeImmutable('2026-03-10'),
        paidAt: new DateTimeImmutable,
        sentAt: new DateTimeImmutable('-1 day'),
        paymentMethod: PaymentMethod::Pix,
        paymentNotes: null,
        notes: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect(fn () => $invoice->send(userId: (string) Uuid::generate()))
        ->toThrow(InvalidInvoiceTransitionException::class);
});

it('throws InvalidInvoiceTransitionException for cancelled -> markPaid', function () {
    $items = createInvoiceItems();

    $invoice = ClientInvoice::reconstitute(
        id: Uuid::generate(),
        clientId: Uuid::generate(),
        contractId: null,
        organizationId: Uuid::generate(),
        referenceMonth: YearMonth::fromString('2026-02'),
        items: $items,
        subtotalCents: 550000,
        discountCents: 0,
        totalCents: 550000,
        currency: Currency::BRL,
        status: InvoiceStatus::Cancelled,
        dueDate: new DateTimeImmutable('2026-03-10'),
        paidAt: null,
        sentAt: null,
        paymentMethod: null,
        paymentNotes: null,
        notes: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    expect(fn () => $invoice->markPaid(
        userId: (string) Uuid::generate(),
        paymentMethod: PaymentMethod::BankTransfer,
    ))->toThrow(InvalidInvoiceTransitionException::class);
});

it('ensures total is at least 0 when discount exceeds subtotal', function () {
    $items = [
        InvoiceItem::create(description: 'Serviço Básico', quantity: 1, unitPriceCents: 10000, position: 1),
    ];

    $invoice = ClientInvoice::create(
        clientId: Uuid::generate(),
        contractId: null,
        organizationId: Uuid::generate(),
        referenceMonth: YearMonth::fromString('2026-02'),
        items: $items,
        discountCents: 99999,
        currency: Currency::BRL,
        dueDate: new DateTimeImmutable('2026-03-10'),
        notes: null,
        userId: (string) Uuid::generate(),
    );

    expect($invoice->totalCents)->toBe(0);
});
