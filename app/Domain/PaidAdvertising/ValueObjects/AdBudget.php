<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

use App\Domain\PaidAdvertising\Exceptions\InsufficientBudgetException;

final readonly class AdBudget
{
    /** @var array<string, int> Minimum budget in cents per provider */
    private const array MIN_BUDGET_CENTS = [
        'meta' => 100,
        'tiktok' => 2000,
        'google' => 500,
    ];

    private function __construct(
        public int $amountCents,
        public string $currency,
        public BudgetType $type,
    ) {}

    public static function create(int $amountCents, string $currency, BudgetType $type): self
    {
        if ($amountCents < 0) {
            throw new InsufficientBudgetException('Valor do orcamento nao pode ser negativo.');
        }

        if (strlen($currency) !== 3) {
            throw new InsufficientBudgetException("Moeda invalida: {$currency}.");
        }

        return new self($amountCents, strtoupper($currency), $type);
    }

    public function validateForProvider(AdProvider $provider): void
    {
        $minCents = self::MIN_BUDGET_CENTS[$provider->value] ?? 100;

        if ($this->amountCents < $minCents) {
            $minDecimal = $minCents / 100;
            throw new InsufficientBudgetException(
                "Orcamento minimo para {$provider->label()} e {$this->currency} {$minDecimal}."
            );
        }
    }

    public function toDecimal(): float
    {
        return $this->amountCents / 100;
    }

    public function isZero(): bool
    {
        return $this->amountCents === 0;
    }

    public function equals(self $other): bool
    {
        return $this->amountCents === $other->amountCents
            && $this->currency === $other->currency
            && $this->type === $other->type;
    }

    /**
     * @return array{amount_cents: int, currency: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'type' => $this->type->value,
        ];
    }
}
