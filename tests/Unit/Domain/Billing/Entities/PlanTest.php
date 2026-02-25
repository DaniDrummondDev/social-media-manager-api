<?php

declare(strict_types=1);

use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Shared\ValueObjects\Uuid;

describe('reconstitute', function () {
    it('creates Plan with correct properties', function () {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        $plan = Plan::reconstitute(
            id: $id,
            name: 'Professional',
            slug: 'professional',
            description: 'For growing teams',
            priceMonthly: Money::fromCents(4900),
            priceYearly: Money::fromCents(49900),
            limits: PlanLimits::fromArray([
                'members' => 10,
                'social_accounts' => 20,
                'publications_month' => 500,
            ]),
            features: PlanFeatures::fromArray([
                'ai_generation_basic' => true,
                'ai_generation_advanced' => true,
                'automations' => true,
            ]),
            isActive: true,
            sortOrder: 3,
            stripePriceMonthlyId: 'price_monthly_123',
            stripePriceYearlyId: 'price_yearly_456',
            createdAt: $now,
            updatedAt: $now,
        );

        expect($plan->id->equals($id))->toBeTrue()
            ->and($plan->name)->toBe('Professional')
            ->and($plan->slug)->toBe('professional')
            ->and($plan->description)->toBe('For growing teams')
            ->and($plan->priceMonthly->amountCents)->toBe(4900)
            ->and($plan->priceYearly->amountCents)->toBe(49900)
            ->and($plan->isActive)->toBeTrue()
            ->and($plan->sortOrder)->toBe(3)
            ->and($plan->stripePriceMonthlyId)->toBe('price_monthly_123')
            ->and($plan->stripePriceYearlyId)->toBe('price_yearly_456');
    });
});

describe('isFree', function () {
    it('returns true when both prices are zero', function () {
        $now = new DateTimeImmutable;

        $plan = Plan::reconstitute(
            id: Uuid::generate(),
            name: 'Free',
            slug: 'free',
            description: 'Free tier',
            priceMonthly: Money::zero(),
            priceYearly: Money::zero(),
            limits: PlanLimits::fromArray([]),
            features: PlanFeatures::fromArray([]),
            isActive: true,
            sortOrder: 1,
            stripePriceMonthlyId: null,
            stripePriceYearlyId: null,
            createdAt: $now,
            updatedAt: $now,
        );

        expect($plan->isFree())->toBeTrue();
    });

    it('returns false for paid plans', function () {
        $now = new DateTimeImmutable;

        $plan = Plan::reconstitute(
            id: Uuid::generate(),
            name: 'Professional',
            slug: 'professional',
            description: 'Paid tier',
            priceMonthly: Money::fromCents(4900),
            priceYearly: Money::fromCents(49900),
            limits: PlanLimits::fromArray([]),
            features: PlanFeatures::fromArray([]),
            isActive: true,
            sortOrder: 2,
            stripePriceMonthlyId: 'price_monthly_123',
            stripePriceYearlyId: 'price_yearly_456',
            createdAt: $now,
            updatedAt: $now,
        );

        expect($plan->isFree())->toBeFalse();
    });
});

describe('getStripePriceId', function () {
    it('returns monthly ID for Monthly cycle', function () {
        $now = new DateTimeImmutable;

        $plan = Plan::reconstitute(
            id: Uuid::generate(),
            name: 'Creator',
            slug: 'creator',
            description: null,
            priceMonthly: Money::fromCents(2900),
            priceYearly: Money::fromCents(29900),
            limits: PlanLimits::fromArray([]),
            features: PlanFeatures::fromArray([]),
            isActive: true,
            sortOrder: 2,
            stripePriceMonthlyId: 'price_monthly_abc',
            stripePriceYearlyId: 'price_yearly_xyz',
            createdAt: $now,
            updatedAt: $now,
        );

        expect($plan->getStripePriceId(BillingCycle::Monthly))->toBe('price_monthly_abc');
    });

    it('returns yearly ID for Yearly cycle', function () {
        $now = new DateTimeImmutable;

        $plan = Plan::reconstitute(
            id: Uuid::generate(),
            name: 'Creator',
            slug: 'creator',
            description: null,
            priceMonthly: Money::fromCents(2900),
            priceYearly: Money::fromCents(29900),
            limits: PlanLimits::fromArray([]),
            features: PlanFeatures::fromArray([]),
            isActive: true,
            sortOrder: 2,
            stripePriceMonthlyId: 'price_monthly_abc',
            stripePriceYearlyId: 'price_yearly_xyz',
            createdAt: $now,
            updatedAt: $now,
        );

        expect($plan->getStripePriceId(BillingCycle::Yearly))->toBe('price_yearly_xyz');
    });
});
