<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

use App\Domain\ClientFinance\Exceptions\InvalidTaxIdException;

final readonly class TaxId
{
    private function __construct(
        public string $value,
        public string $type,
    ) {}

    public static function fromString(string $value): self
    {
        $digits = preg_replace('/\D/', '', $value);

        if ($digits === null || $digits === '') {
            throw new InvalidTaxIdException("Tax ID não pode ser vazio.");
        }

        return match (strlen($digits)) {
            11 => self::createCpf($digits),
            14 => self::createCnpj($digits),
            default => throw new InvalidTaxIdException("Tax ID deve ter 11 (CPF) ou 14 (CNPJ) dígitos, recebeu " . strlen($digits) . "."),
        };
    }

    public function formatted(): string
    {
        return match ($this->type) {
            'cpf' => sprintf(
                '%s.%s.%s-%s',
                substr($this->value, 0, 3),
                substr($this->value, 3, 3),
                substr($this->value, 6, 3),
                substr($this->value, 9, 2),
            ),
            'cnpj' => sprintf(
                '%s.%s.%s/%s-%s',
                substr($this->value, 0, 2),
                substr($this->value, 2, 3),
                substr($this->value, 5, 3),
                substr($this->value, 8, 4),
                substr($this->value, 12, 2),
            ),
            default => $this->value,
        };
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function createCpf(string $digits): self
    {
        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            throw new InvalidTaxIdException("CPF inválido: todos os dígitos são iguais.");
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $digits[$i] * (10 - $i);
        }
        $remainder = ($sum * 10) % 11;
        if ($remainder === 10) {
            $remainder = 0;
        }
        if ($remainder !== (int) $digits[9]) {
            throw new InvalidTaxIdException("CPF inválido: dígito verificador incorreto.");
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $digits[$i] * (11 - $i);
        }
        $remainder = ($sum * 10) % 11;
        if ($remainder === 10) {
            $remainder = 0;
        }
        if ($remainder !== (int) $digits[10]) {
            throw new InvalidTaxIdException("CPF inválido: dígito verificador incorreto.");
        }

        return new self($digits, 'cpf');
    }

    private static function createCnpj(string $digits): self
    {
        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            throw new InvalidTaxIdException("CNPJ inválido: todos os dígitos são iguais.");
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
        if ($digit1 !== (int) $digits[12]) {
            throw new InvalidTaxIdException("CNPJ inválido: dígito verificador incorreto.");
        }

        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $digits[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
        if ($digit2 !== (int) $digits[13]) {
            throw new InvalidTaxIdException("CNPJ inválido: dígito verificador incorreto.");
        }

        return new self($digits, 'cnpj');
    }
}
