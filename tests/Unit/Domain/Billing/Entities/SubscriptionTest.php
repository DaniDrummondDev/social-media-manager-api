<?php

declare(strict_types=1);

use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Events\PaymentFailed;
use App\Domain\Billing\Events\SubscriptionCanceled;
use App\Domain\Billing\Events\SubscriptionCreated;
use App\Domain\Billing\Events\SubscriptionExpired;
use App\Domain\Billing\Events\SubscriptionReactivated;
use App\Domain\Billing\Events\SubscriptionUpgraded;
use App\Domain\Billing\Exceptions\InvalidSubscriptionTransitionException;
use App\Domain\Billing\Exceptions\SubscriptionAlreadyCanceledException;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createActiveSubscription(): Subscription
{
    $now = new DateTimeImmutable;

    return Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        planId: Uuid::generate(),
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: $now,
        currentPeriodEnd: $now->modify('+30 days'),
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: null,
        externalCustomerId: null,
        createdAt: $now,
        updatedAt: $now,
    );
}

function createTrialingSubscription(): Subscription
{
    $now = new DateTimeImmutable;

    return Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        planId: Uuid::generate(),
        status: SubscriptionStatus::Trialing,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: $now,
        currentPeriodEnd: $now->modify('+14 days'),
        trialEndsAt: $now->modify('+14 days'),
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: null,
        externalCustomerId: null,
        createdAt: $now,
        updatedAt: $now,
    );
}

describe('createFree', function () {
    it('creates with Active status and dispatches SubscriptionCreated event', function () {
        $orgId = Uuid::generate();
        $planId = Uuid::generate();

        $subscription = Subscription::createFree(
            organizationId: $orgId,
            freePlanId: $planId,
            userId: 'user-123',
        );

        expect($subscription->status)->toBe(SubscriptionStatus::Active)
            ->and($subscription->billingCycle)->toBe(BillingCycle::Monthly)
            ->and($subscription->organizationId->equals($orgId))->toBeTrue()
            ->and($subscription->planId->equals($planId))->toBeTrue()
            ->and($subscription->cancelAtPeriodEnd)->toBeFalse()
            ->and($subscription->canceledAt)->toBeNull()
            ->and($subscription->externalSubscriptionId)->toBeNull()
            ->and($subscription->externalCustomerId)->toBeNull()
            ->and($subscription->domainEvents)->toHaveCount(1)
            ->and($subscription->domainEvents[0])->toBeInstanceOf(SubscriptionCreated::class);
    });
});

describe('cancel', function () {
    it('sets cancelAtPeriodEnd to true and dispatches SubscriptionCanceled', function () {
        $subscription = createActiveSubscription();
        $canceled = $subscription->cancel('Too expensive');

        expect($canceled->cancelAtPeriodEnd)->toBeTrue()
            ->and($canceled->canceledAt)->not->toBeNull()
            ->and($canceled->cancelReason)->toBe('Too expensive')
            ->and($canceled->status)->toBe(SubscriptionStatus::Active)
            ->and($canceled->domainEvents)->toHaveCount(1)
            ->and($canceled->domainEvents[0])->toBeInstanceOf(SubscriptionCanceled::class);
    });

    it('throws SubscriptionAlreadyCanceledException if already canceling', function () {
        $subscription = createActiveSubscription();
        $canceled = $subscription->cancel('Reason');
        $canceled->cancel('Another reason');
    })->throws(SubscriptionAlreadyCanceledException::class);
});

describe('reactivate', function () {
    it('clears cancelAtPeriodEnd and dispatches SubscriptionReactivated', function () {
        $subscription = createActiveSubscription();
        $canceled = $subscription->cancel('Reason');
        $reactivated = $canceled->reactivate();

        expect($reactivated->cancelAtPeriodEnd)->toBeFalse()
            ->and($reactivated->canceledAt)->toBeNull()
            ->and($reactivated->cancelReason)->toBeNull()
            ->and($reactivated->cancelFeedback)->toBeNull()
            ->and($reactivated->domainEvents)->toHaveCount(1)
            ->and($reactivated->domainEvents[0])->toBeInstanceOf(SubscriptionReactivated::class);
    });

    it('throws InvalidSubscriptionTransitionException if not active and canceling', function () {
        $subscription = createActiveSubscription();
        $subscription->reactivate();
    })->throws(InvalidSubscriptionTransitionException::class);
});

describe('markPastDue', function () {
    it('changes status to PastDue and dispatches PaymentFailed', function () {
        $subscription = createActiveSubscription();
        $pastDue = $subscription->markPastDue();

        expect($pastDue->status)->toBe(SubscriptionStatus::PastDue)
            ->and($pastDue->domainEvents)->toHaveCount(1)
            ->and($pastDue->domainEvents[0])->toBeInstanceOf(PaymentFailed::class);
    });
});

describe('expire', function () {
    it('changes status to Expired and dispatches SubscriptionExpired', function () {
        $subscription = createActiveSubscription();
        $expired = $subscription->expire();

        expect($expired->status)->toBe(SubscriptionStatus::Expired)
            ->and($expired->domainEvents)->toHaveCount(1)
            ->and($expired->domainEvents[0])->toBeInstanceOf(SubscriptionExpired::class);
    });
});

describe('upgrade', function () {
    it('changes planId and dispatches SubscriptionUpgraded', function () {
        $subscription = createActiveSubscription();
        $newPlanId = Uuid::generate();
        $oldPlanId = $subscription->planId;
        $upgraded = $subscription->upgrade($newPlanId);

        expect($upgraded->planId->equals($newPlanId))->toBeTrue()
            ->and($upgraded->cancelAtPeriodEnd)->toBeFalse()
            ->and($upgraded->canceledAt)->toBeNull()
            ->and($upgraded->domainEvents)->toHaveCount(1)
            ->and($upgraded->domainEvents[0])->toBeInstanceOf(SubscriptionUpgraded::class)
            ->and($upgraded->domainEvents[0]->oldPlanId)->toBe((string) $oldPlanId)
            ->and($upgraded->domainEvents[0]->newPlanId)->toBe((string) $newPlanId);
    });

    it('preserves subscription identity and billing details after upgrade', function () {
        $subscription = createActiveSubscription();
        $newPlanId = Uuid::generate();
        $upgraded = $subscription->upgrade($newPlanId);

        expect($upgraded->id->equals($subscription->id))->toBeTrue()
            ->and($upgraded->organizationId->equals($subscription->organizationId))->toBeTrue()
            ->and($upgraded->billingCycle)->toBe($subscription->billingCycle)
            ->and($upgraded->status)->toBe($subscription->status);
    });
});

describe('isActive', function () {
    it('returns true for Active status', function () {
        $subscription = createActiveSubscription();

        expect($subscription->isActive())->toBeTrue();
    });

    it('returns true for Trialing status', function () {
        $subscription = createTrialingSubscription();

        expect($subscription->isActive())->toBeTrue();
    });

    it('returns false for Expired status', function () {
        $subscription = createActiveSubscription();
        $expired = $subscription->expire();

        expect($expired->isActive())->toBeFalse();
    });
});

describe('canCancel', function () {
    it('returns true for Active subscription not already canceling', function () {
        $subscription = createActiveSubscription();

        expect($subscription->canCancel())->toBeTrue();
    });

    it('returns true for Trialing subscription not already canceling', function () {
        $subscription = createTrialingSubscription();

        expect($subscription->canCancel())->toBeTrue();
    });

    it('returns false when already canceling', function () {
        $subscription = createActiveSubscription();
        $canceled = $subscription->cancel('Reason');

        expect($canceled->canCancel())->toBeFalse();
    });

    it('returns false for Expired subscription', function () {
        $subscription = createActiveSubscription();
        $expired = $subscription->expire();

        expect($expired->canCancel())->toBeFalse();
    });
});
