<?php

declare(strict_types=1);

use App\Domain\ClientFinance\Exceptions\InvalidTaxIdException;
use App\Domain\ClientFinance\ValueObjects\TaxId;

describe('CPF', function () {
    it('creates a valid CPF from digits only', function () {
        $taxId = TaxId::fromString('52998224725');

        expect($taxId->value)->toBe('52998224725')
            ->and($taxId->type)->toBe('cpf');
    });

    it('creates a valid CPF from formatted string', function () {
        $taxId = TaxId::fromString('529.982.247-25');

        expect($taxId->value)->toBe('52998224725')
            ->and($taxId->type)->toBe('cpf');
    });

    it('throws for CPF with all same digits', function () {
        expect(fn () => TaxId::fromString('11111111111'))
            ->toThrow(InvalidTaxIdException::class);
    });

    it('throws for CPF with wrong check digits', function () {
        expect(fn () => TaxId::fromString('52998224726'))
            ->toThrow(InvalidTaxIdException::class);
    });

    it('formats CPF correctly', function () {
        $taxId = TaxId::fromString('52998224725');

        expect($taxId->formatted())->toBe('529.982.247-25');
    });
});

describe('CNPJ', function () {
    it('creates a valid CNPJ from digits only', function () {
        $taxId = TaxId::fromString('11222333000181');

        expect($taxId->value)->toBe('11222333000181')
            ->and($taxId->type)->toBe('cnpj');
    });

    it('creates a valid CNPJ from formatted string', function () {
        $taxId = TaxId::fromString('11.222.333/0001-81');

        expect($taxId->value)->toBe('11222333000181')
            ->and($taxId->type)->toBe('cnpj');
    });

    it('throws for CNPJ with all same digits', function () {
        expect(fn () => TaxId::fromString('11111111111111'))
            ->toThrow(InvalidTaxIdException::class);
    });

    it('throws for CNPJ with wrong check digits', function () {
        expect(fn () => TaxId::fromString('11222333000182'))
            ->toThrow(InvalidTaxIdException::class);
    });

    it('formats CNPJ correctly', function () {
        $taxId = TaxId::fromString('11222333000181');

        expect($taxId->formatted())->toBe('11.222.333/0001-81');
    });
});

describe('Validation', function () {
    it('throws for empty string', function () {
        expect(fn () => TaxId::fromString(''))
            ->toThrow(InvalidTaxIdException::class);
    });

    it('throws for wrong length', function () {
        expect(fn () => TaxId::fromString('12345'))
            ->toThrow(InvalidTaxIdException::class);
    });
});

describe('Equality', function () {
    it('returns true for equal TaxIds', function () {
        $a = TaxId::fromString('52998224725');
        $b = TaxId::fromString('529.982.247-25');

        expect($a->equals($b))->toBeTrue();
    });

    it('returns false for different TaxIds', function () {
        $cpf = TaxId::fromString('52998224725');
        $cnpj = TaxId::fromString('11222333000181');

        expect($cpf->equals($cnpj))->toBeFalse();
    });

    it('converts to string using __toString', function () {
        $taxId = TaxId::fromString('52998224725');

        expect((string) $taxId)->toBe('52998224725');
    });
});
