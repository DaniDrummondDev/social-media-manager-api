<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Services;

use App\Domain\ClientFinance\Entities\InvoiceItem;

final readonly class InvoiceCalculationService
{
    /**
     * @param  array<InvoiceItem>  $items
     */
    public function calculateSubtotal(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item->totalCents;
        }

        return $total;
    }

    public function calculateTotal(int $subtotalCents, int $discountCents): int
    {
        return max(0, $subtotalCents - $discountCents);
    }

    /**
     * @return array{profit_cents: int, margin_percent: float}
     */
    public function calculateProfitability(int $revenueCents, int $costCents): array
    {
        $profitCents = $revenueCents - $costCents;
        $marginPercent = $revenueCents > 0
            ? round(($profitCents / $revenueCents) * 100, 2)
            : 0.0;

        return [
            'profit_cents' => $profitCents,
            'margin_percent' => $marginPercent,
        ];
    }
}
