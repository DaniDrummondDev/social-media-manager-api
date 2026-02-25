<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

use App\Domain\Billing\Exceptions\InvalidMoneyException;

final readonly class Money
{
    private function __construct(
        public int $amountCents,
        public string $currency,
    ) {}

    public static function fromCents(int $amountCents, string $currency = 'BRL'): self
    {
        if ($amountCents < 0) {
            throw new InvalidMoneyException("Valor não pode ser negativo: {$amountCents}.");
        }

        if (strlen($currency) !== 3) {
            throw new InvalidMoneyException("Moeda inválida: {$currency}.");
        }

        return new self($amountCents, strtoupper($currency));
    }

    public static function zero(string $currency = 'BRL'): self
    {
        return new self(0, strtoupper($currency));
    }

    public function toDecimal(): float
    {
        return $this->amountCents / 100;
    }

    public function formatBRL(): string
    {
        return 'R$ '.number_format($this->toDecimal(), 2, ',', '.');
    }

    public function equals(self $other): bool
    {
        return $this->amountCents === $other->amountCents
            && $this->currency === $other->currency;
    }

    public function isZero(): bool
    {
        return $this->amountCents === 0;
    }
}
