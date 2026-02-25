<?php

declare(strict_types=1);

use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Billing\Repositories\EloquentSubscriptionRepository;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(PlanSeeder::class);

    $this->orgId = (string) Str::uuid();
    $this->userId = (string) Str::uuid();
    $this->planId = PlanSeeder::CREATOR_PLAN_ID;

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'test-'.Str::random(6).'@test.com',
        'password' => 'hashed',
        'timezone' => 'UTC',
        'two_factor_enabled' => false,
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'test-'.Str::random(4),
        'timezone' => 'UTC',
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('create persists subscription', function () {
    $repo = app(EloquentSubscriptionRepository::class);

    $now = new DateTimeImmutable;
    $periodStart = new DateTimeImmutable('first day of this month midnight');
    $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        planId: Uuid::fromString($this->planId),
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: $periodStart,
        currentPeriodEnd: $periodEnd,
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_stripe_123',
        externalCustomerId: 'cus_stripe_456',
        createdAt: $now,
        updatedAt: $now,
    );

    $repo->create($subscription);

    $found = $repo->findById($subscription->id);

    expect($found)->not->toBeNull()
        ->and($found)->toBeInstanceOf(Subscription::class)
        ->and((string) $found->id)->toBe((string) $subscription->id)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and((string) $found->planId)->toBe($this->planId)
        ->and($found->status)->toBe(SubscriptionStatus::Active)
        ->and($found->billingCycle)->toBe(BillingCycle::Monthly)
        ->and($found->externalSubscriptionId)->toBe('sub_stripe_123')
        ->and($found->externalCustomerId)->toBe('cus_stripe_456')
        ->and($found->cancelAtPeriodEnd)->toBeFalse()
        ->and($found->cancelReason)->toBeNull()
        ->and($found->cancelFeedback)->toBeNull();
});

it('findActiveByOrganization returns active subscription', function () {
    $repo = app(EloquentSubscriptionRepository::class);

    $now = new DateTimeImmutable;
    $periodStart = new DateTimeImmutable('first day of this month midnight');
    $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        planId: Uuid::fromString($this->planId),
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: $periodStart,
        currentPeriodEnd: $periodEnd,
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_active_001',
        externalCustomerId: 'cus_001',
        createdAt: $now,
        updatedAt: $now,
    );

    $repo->create($subscription);

    $found = $repo->findActiveByOrganization(Uuid::fromString($this->orgId));

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $subscription->id)
        ->and($found->status)->toBe(SubscriptionStatus::Active);
});

it('findByExternalId returns correct subscription', function () {
    $repo = app(EloquentSubscriptionRepository::class);

    $now = new DateTimeImmutable;
    $periodStart = new DateTimeImmutable('first day of this month midnight');
    $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');
    $externalId = 'sub_ext_'.Str::random(8);

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        planId: Uuid::fromString($this->planId),
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Yearly,
        currentPeriodStart: $periodStart,
        currentPeriodEnd: $periodEnd,
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: $externalId,
        externalCustomerId: 'cus_ext_001',
        createdAt: $now,
        updatedAt: $now,
    );

    $repo->create($subscription);

    $found = $repo->findByExternalId($externalId);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $subscription->id)
        ->and($found->externalSubscriptionId)->toBe($externalId)
        ->and($found->billingCycle)->toBe(BillingCycle::Yearly);
});

it('findExpiredPastDue returns past_due subscriptions older than threshold', function () {
    $repo = app(EloquentSubscriptionRepository::class);

    $now = new DateTimeImmutable;
    $periodStart = new DateTimeImmutable('first day of this month midnight');
    $periodEnd = new DateTimeImmutable('last day of this month 23:59:59');

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        planId: Uuid::fromString($this->planId),
        status: SubscriptionStatus::PastDue,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: $periodStart,
        currentPeriodEnd: $periodEnd,
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_past_due_001',
        externalCustomerId: 'cus_pd_001',
        createdAt: $now,
        updatedAt: $now,
    );

    $repo->create($subscription);

    // Backdate updated_at to simulate old past_due record
    DB::table('subscriptions')
        ->where('id', (string) $subscription->id)
        ->update(['updated_at' => now()->subDays(10)->toDateTimeString()]);

    $threshold = new DateTimeImmutable('-5 days');
    $expired = $repo->findExpiredPastDue($threshold);

    expect($expired)->toHaveCount(1)
        ->and((string) $expired[0]->id)->toBe((string) $subscription->id)
        ->and($expired[0]->status)->toBe(SubscriptionStatus::PastDue);

    // Threshold in the future should not return recent records
    $futureThreshold = new DateTimeImmutable('-30 days');
    $none = $repo->findExpiredPastDue($futureThreshold);

    expect($none)->toHaveCount(0);
});

it('findCanceledEndingBefore returns canceled subscriptions past period end', function () {
    $repo = app(EloquentSubscriptionRepository::class);

    $now = new DateTimeImmutable;
    $periodStart = new DateTimeImmutable('-60 days');
    $periodEnd = new DateTimeImmutable('-5 days');

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        planId: Uuid::fromString($this->planId),
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: $periodStart,
        currentPeriodEnd: $periodEnd,
        trialEndsAt: null,
        canceledAt: new DateTimeImmutable('-10 days'),
        cancelAtPeriodEnd: true,
        cancelReason: 'Too expensive',
        cancelFeedback: null,
        externalSubscriptionId: 'sub_cancel_001',
        externalCustomerId: 'cus_cancel_001',
        createdAt: $now,
        updatedAt: $now,
    );

    $repo->create($subscription);

    $date = new DateTimeImmutable('now');
    $canceled = $repo->findCanceledEndingBefore($date);

    expect($canceled)->toHaveCount(1)
        ->and((string) $canceled[0]->id)->toBe((string) $subscription->id)
        ->and($canceled[0]->cancelAtPeriodEnd)->toBeTrue()
        ->and($canceled[0]->cancelReason)->toBe('Too expensive');

    // Date before period_end should return nothing
    $earlyDate = new DateTimeImmutable('-30 days');
    $none = $repo->findCanceledEndingBefore($earlyDate);

    expect($none)->toHaveCount(0);
});
