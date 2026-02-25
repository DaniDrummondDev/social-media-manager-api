<?php

declare(strict_types=1);

use App\Domain\ClientFinance\ValueObjects\InvoiceStatus;

describe('Valid transitions', function () {
    it('allows draft -> sent', function () {
        expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Sent))->toBeTrue();
    });

    it('allows draft -> cancelled', function () {
        expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Cancelled))->toBeTrue();
    });

    it('allows sent -> paid', function () {
        expect(InvoiceStatus::Sent->canTransitionTo(InvoiceStatus::Paid))->toBeTrue();
    });

    it('allows sent -> overdue', function () {
        expect(InvoiceStatus::Sent->canTransitionTo(InvoiceStatus::Overdue))->toBeTrue();
    });

    it('allows sent -> cancelled', function () {
        expect(InvoiceStatus::Sent->canTransitionTo(InvoiceStatus::Cancelled))->toBeTrue();
    });

    it('allows overdue -> paid', function () {
        expect(InvoiceStatus::Overdue->canTransitionTo(InvoiceStatus::Paid))->toBeTrue();
    });

    it('allows overdue -> cancelled', function () {
        expect(InvoiceStatus::Overdue->canTransitionTo(InvoiceStatus::Cancelled))->toBeTrue();
    });
});

describe('Invalid transitions', function () {
    it('disallows draft -> paid', function () {
        expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Paid))->toBeFalse();
    });

    it('disallows draft -> overdue', function () {
        expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Overdue))->toBeFalse();
    });

    it('disallows paid -> any status', function () {
        expect(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Draft))->toBeFalse()
            ->and(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Sent))->toBeFalse()
            ->and(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Overdue))->toBeFalse()
            ->and(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Cancelled))->toBeFalse();
    });

    it('disallows cancelled -> any status', function () {
        expect(InvoiceStatus::Cancelled->canTransitionTo(InvoiceStatus::Draft))->toBeFalse()
            ->and(InvoiceStatus::Cancelled->canTransitionTo(InvoiceStatus::Sent))->toBeFalse()
            ->and(InvoiceStatus::Cancelled->canTransitionTo(InvoiceStatus::Paid))->toBeFalse()
            ->and(InvoiceStatus::Cancelled->canTransitionTo(InvoiceStatus::Overdue))->toBeFalse();
    });
});

describe('isEditable', function () {
    it('returns true for draft', function () {
        expect(InvoiceStatus::Draft->isEditable())->toBeTrue();
    });

    it('returns false for sent', function () {
        expect(InvoiceStatus::Sent->isEditable())->toBeFalse();
    });

    it('returns false for paid', function () {
        expect(InvoiceStatus::Paid->isEditable())->toBeFalse();
    });

    it('returns false for overdue', function () {
        expect(InvoiceStatus::Overdue->isEditable())->toBeFalse();
    });

    it('returns false for cancelled', function () {
        expect(InvoiceStatus::Cancelled->isEditable())->toBeFalse();
    });
});
