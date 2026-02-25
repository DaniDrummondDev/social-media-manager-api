<?php

declare(strict_types=1);

use App\Domain\ClientFinance\Entities\InvoiceItem;
use App\Domain\ClientFinance\Services\InvoiceCalculationService;

beforeEach(function () {
    $this->service = new InvoiceCalculationService;
});

describe('calculateSubtotal', function () {
    it('sums all item totalCents', function () {
        $items = [
            InvoiceItem::create(description: 'Serviço A', quantity: 1, unitPriceCents: 300000, position: 1),
            InvoiceItem::create(description: 'Serviço B', quantity: 5, unitPriceCents: 50000, position: 2),
            InvoiceItem::create(description: 'Serviço C', quantity: 2, unitPriceCents: 100000, position: 3),
        ];

        // 300000 + 250000 + 200000 = 750000
        $subtotal = $this->service->calculateSubtotal($items);

        expect($subtotal)->toBe(750000);
    });

    it('returns 0 for empty items array', function () {
        $subtotal = $this->service->calculateSubtotal([]);

        expect($subtotal)->toBe(0);
    });

    it('handles single item', function () {
        $items = [
            InvoiceItem::create(description: 'Único', quantity: 3, unitPriceCents: 10000, position: 1),
        ];

        $subtotal = $this->service->calculateSubtotal($items);

        expect($subtotal)->toBe(30000);
    });
});

describe('calculateTotal', function () {
    it('returns subtotal minus discount', function () {
        $total = $this->service->calculateTotal(500000, 50000);

        expect($total)->toBe(450000);
    });

    it('returns 0 when discount exceeds subtotal', function () {
        $total = $this->service->calculateTotal(100000, 200000);

        expect($total)->toBe(0);
    });

    it('returns subtotal when discount is 0', function () {
        $total = $this->service->calculateTotal(500000, 0);

        expect($total)->toBe(500000);
    });

    it('returns 0 when subtotal equals discount', function () {
        $total = $this->service->calculateTotal(100000, 100000);

        expect($total)->toBe(0);
    });
});

describe('calculateProfitability', function () {
    it('returns profit_cents and margin_percent', function () {
        $result = $this->service->calculateProfitability(revenueCents: 1000000, costCents: 400000);

        expect($result['profit_cents'])->toBe(600000)
            ->and($result['margin_percent'])->toBe(60.0);
    });

    it('returns 0.0 margin when revenue is 0', function () {
        $result = $this->service->calculateProfitability(revenueCents: 0, costCents: 50000);

        expect($result['profit_cents'])->toBe(-50000)
            ->and($result['margin_percent'])->toBe(0.0);
    });

    it('returns 100% margin when cost is 0', function () {
        $result = $this->service->calculateProfitability(revenueCents: 500000, costCents: 0);

        expect($result['profit_cents'])->toBe(500000)
            ->and($result['margin_percent'])->toBe(100.0);
    });

    it('returns negative profit when cost exceeds revenue', function () {
        $result = $this->service->calculateProfitability(revenueCents: 100000, costCents: 150000);

        expect($result['profit_cents'])->toBe(-50000)
            ->and($result['margin_percent'])->toBe(-50.0);
    });

    it('rounds margin to 2 decimal places', function () {
        $result = $this->service->calculateProfitability(revenueCents: 300000, costCents: 100000);

        // profit: 200000, margin: 200000/300000 * 100 = 66.67
        expect($result['profit_cents'])->toBe(200000)
            ->and($result['margin_percent'])->toBe(66.67);
    });
});
