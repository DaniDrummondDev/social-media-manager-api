<?php

declare(strict_types=1);

use App\Domain\Billing\Exceptions\InvalidMoneyException;
use App\Domain\Billing\ValueObjects\Money;

it('creates from cents with correct amount and currency', function () {
    $money = Money::fromCents(4900, 'USD');

    expect($money->amountCents)->toBe(4900)
        ->and($money->currency)->toBe('USD');
});

it('defaults to BRL currency', function () {
    $money = Money::fromCents(1000);

    expect($money->currency)->toBe('BRL');
});

it('throws InvalidMoneyException for negative amount', function () {
    Money::fromCents(-100);
})->throws(InvalidMoneyException::class);

it('creates zero money', function () {
    $money = Money::zero();

    expect($money->amountCents)->toBe(0)
        ->and($money->currency)->toBe('BRL');
});

it('returns correct decimal value', function () {
    $money = Money::fromCents(4900);

    expect($money->toDecimal())->toBe(49.0);
});

it('formats BRL correctly', function () {
    $money = Money::fromCents(4900);

    expect($money->formatBRL())->toBe('R$ 49,00');
});

it('equals returns true for same values', function () {
    $a = Money::fromCents(4900, 'BRL');
    $b = Money::fromCents(4900, 'BRL');

    expect($a->equals($b))->toBeTrue();
});

it('equals returns false for different amount', function () {
    $a = Money::fromCents(4900, 'BRL');
    $b = Money::fromCents(9900, 'BRL');

    expect($a->equals($b))->toBeFalse();
});

it('equals returns false for different currency', function () {
    $a = Money::fromCents(4900, 'BRL');
    $b = Money::fromCents(4900, 'USD');

    expect($a->equals($b))->toBeFalse();
});

it('isZero returns true for zero amount', function () {
    $money = Money::zero();

    expect($money->isZero())->toBeTrue();
});

it('isZero returns false for non-zero amount', function () {
    $money = Money::fromCents(100);

    expect($money->isZero())->toBeFalse();
});
