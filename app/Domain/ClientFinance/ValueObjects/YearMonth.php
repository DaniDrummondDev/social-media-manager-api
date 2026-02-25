<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

use App\Domain\ClientFinance\Exceptions\InvalidYearMonthException;
use DateTimeImmutable;

final readonly class YearMonth
{
    private function __construct(
        public int $year,
        public int $month,
    ) {}

    public static function fromString(string $value): self
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            throw new InvalidYearMonthException("Formato inválido: '{$value}'. Esperado YYYY-MM.");
        }

        [$year, $month] = explode('-', $value);

        return new self((int) $year, (int) $month);
    }

    public static function current(): self
    {
        $now = new DateTimeImmutable;

        return new self((int) $now->format('Y'), (int) $now->format('m'));
    }

    public function toString(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function equals(self $other): bool
    {
        return $this->year === $other->year && $this->month === $other->month;
    }

    public function startOfMonth(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $this->year, $this->month));
    }

    public function endOfMonth(): DateTimeImmutable
    {
        return $this->startOfMonth()->modify('last day of this month 23:59:59');
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
