<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\Exceptions\InsufficientBudgetException;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;

it('creates budget with valid values', function () {
    $budget = AdBudget::create(1500, 'usd', BudgetType::Daily);

    expect($budget->amountCents)->toBe(1500)
        ->and($budget->currency)->toBe('USD')
        ->and($budget->type)->toBe(BudgetType::Daily);
});

it('normalizes currency to uppercase', function () {
    $budget = AdBudget::create(100, 'brl', BudgetType::Lifetime);

    expect($budget->currency)->toBe('BRL');
});

it('rejects negative amount', function () {
    AdBudget::create(-1, 'USD', BudgetType::Daily);
})->throws(InsufficientBudgetException::class);

it('rejects currency with wrong length', function () {
    AdBudget::create(100, 'US', BudgetType::Daily);
})->throws(InsufficientBudgetException::class);

it('validates minimum budget for meta', function () {
    $budget = AdBudget::create(100, 'USD', BudgetType::Daily);
    $budget->validateForProvider(AdProvider::Meta);

    $under = AdBudget::create(99, 'USD', BudgetType::Daily);
    expect(fn () => $under->validateForProvider(AdProvider::Meta))
        ->toThrow(InsufficientBudgetException::class);
});

it('validates minimum budget for tiktok', function () {
    $budget = AdBudget::create(2000, 'USD', BudgetType::Daily);
    $budget->validateForProvider(AdProvider::TikTok);

    $under = AdBudget::create(1999, 'USD', BudgetType::Daily);
    expect(fn () => $under->validateForProvider(AdProvider::TikTok))
        ->toThrow(InsufficientBudgetException::class);
});

it('validates minimum budget for google', function () {
    $budget = AdBudget::create(500, 'USD', BudgetType::Daily);
    $budget->validateForProvider(AdProvider::Google);

    $under = AdBudget::create(499, 'USD', BudgetType::Daily);
    expect(fn () => $under->validateForProvider(AdProvider::Google))
        ->toThrow(InsufficientBudgetException::class);
});

it('converts to decimal', function () {
    $budget = AdBudget::create(1500, 'USD', BudgetType::Daily);

    expect($budget->toDecimal())->toBe(15.0);
});

it('detects zero budget', function () {
    $zero = AdBudget::create(0, 'USD', BudgetType::Daily);
    $nonZero = AdBudget::create(100, 'USD', BudgetType::Daily);

    expect($zero->isZero())->toBeTrue()
        ->and($nonZero->isZero())->toBeFalse();
});

it('compares equality', function () {
    $a = AdBudget::create(1000, 'USD', BudgetType::Daily);
    $b = AdBudget::create(1000, 'USD', BudgetType::Daily);
    $c = AdBudget::create(2000, 'USD', BudgetType::Lifetime);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});

it('serializes to array', function () {
    $budget = AdBudget::create(1500, 'USD', BudgetType::Daily);

    expect($budget->toArray())->toBe([
        'amount_cents' => 1500,
        'currency' => 'USD',
        'type' => 'daily',
    ]);
});
