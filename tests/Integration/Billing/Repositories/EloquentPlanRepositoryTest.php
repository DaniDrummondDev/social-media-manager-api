<?php

declare(strict_types=1);

use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Billing\Repositories\EloquentPlanRepository;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
});

it('findAllActive returns 4 plans sorted by sort_order', function () {
    $repo = app(EloquentPlanRepository::class);

    $plans = $repo->findAllActive();

    expect($plans)->toHaveCount(4)
        ->and($plans[0]->slug)->toBe('free')
        ->and($plans[0]->sortOrder)->toBe(1)
        ->and($plans[1]->slug)->toBe('creator')
        ->and($plans[1]->sortOrder)->toBe(2)
        ->and($plans[2]->slug)->toBe('professional')
        ->and($plans[2]->sortOrder)->toBe(3)
        ->and($plans[3]->slug)->toBe('agency')
        ->and($plans[3]->sortOrder)->toBe(4);

    foreach ($plans as $plan) {
        expect($plan)->toBeInstanceOf(Plan::class);
    }
});

it('findBySlug returns correct plan', function () {
    $repo = app(EloquentPlanRepository::class);

    $plan = $repo->findBySlug('creator');

    expect($plan)->not->toBeNull()
        ->and($plan)->toBeInstanceOf(Plan::class)
        ->and($plan->name)->toBe('Creator')
        ->and($plan->slug)->toBe('creator')
        ->and($plan->priceMonthly->amountCents)->toBe(4900)
        ->and($plan->priceYearly->amountCents)->toBe(49000)
        ->and($plan->isActive)->toBeTrue()
        ->and($plan->sortOrder)->toBe(2);
});

it('findBySlug returns null for unknown slug', function () {
    $repo = app(EloquentPlanRepository::class);

    $plan = $repo->findBySlug('nonexistent-plan');

    expect($plan)->toBeNull();
});

it('findFreePlan returns the free plan', function () {
    $repo = app(EloquentPlanRepository::class);

    $plan = $repo->findFreePlan();

    expect($plan)->not->toBeNull()
        ->and($plan)->toBeInstanceOf(Plan::class)
        ->and($plan->slug)->toBe('free')
        ->and($plan->name)->toBe('Free')
        ->and($plan->priceMonthly->amountCents)->toBe(0)
        ->and($plan->priceYearly->amountCents)->toBe(0)
        ->and($plan->isFree())->toBeTrue();
});

it('findById returns correct plan with all VOs', function () {
    $repo = app(EloquentPlanRepository::class);

    $plan = $repo->findById(Uuid::fromString(PlanSeeder::PROFESSIONAL_PLAN_ID));

    expect($plan)->not->toBeNull()
        ->and($plan)->toBeInstanceOf(Plan::class)
        ->and((string) $plan->id)->toBe(PlanSeeder::PROFESSIONAL_PLAN_ID)
        ->and($plan->name)->toBe('Professional')
        ->and($plan->slug)->toBe('professional');

    // Money VO
    expect($plan->priceMonthly)->toBeInstanceOf(Money::class)
        ->and($plan->priceMonthly->amountCents)->toBe(14900)
        ->and($plan->priceMonthly->currency)->toBe('BRL')
        ->and($plan->priceYearly)->toBeInstanceOf(Money::class)
        ->and($plan->priceYearly->amountCents)->toBe(149000)
        ->and($plan->priceYearly->currency)->toBe('BRL');

    // PlanLimits VO
    expect($plan->limits)->toBeInstanceOf(PlanLimits::class)
        ->and($plan->limits->members)->toBe(5)
        ->and($plan->limits->socialAccounts)->toBe(15)
        ->and($plan->limits->publicationsMonth)->toBe(500)
        ->and($plan->limits->aiGenerationsMonth)->toBe(500)
        ->and($plan->limits->storageGb)->toBe(15)
        ->and($plan->limits->activeCampaigns)->toBe(30)
        ->and($plan->limits->automations)->toBe(15)
        ->and($plan->limits->webhooks)->toBe(5)
        ->and($plan->limits->reportsMonth)->toBe(50)
        ->and($plan->limits->analyticsRetentionDays)->toBe(180);

    // PlanFeatures VO
    expect($plan->features)->toBeInstanceOf(PlanFeatures::class)
        ->and($plan->features->aiGenerationBasic)->toBeTrue()
        ->and($plan->features->aiGenerationAdvanced)->toBeTrue()
        ->and($plan->features->aiIntelligence)->toBeTrue()
        ->and($plan->features->aiLearning)->toBeTrue()
        ->and($plan->features->automations)->toBeTrue()
        ->and($plan->features->webhooks)->toBeTrue()
        ->and($plan->features->crmNative)->toBeTrue()
        ->and($plan->features->exportPdf)->toBeTrue()
        ->and($plan->features->exportCsv)->toBeTrue()
        ->and($plan->features->priorityPublishing)->toBeTrue();

    // Timestamps
    expect($plan->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($plan->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
});
