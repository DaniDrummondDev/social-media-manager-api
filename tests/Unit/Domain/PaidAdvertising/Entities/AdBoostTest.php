<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\Events\BoostActivated;
use App\Domain\PaidAdvertising\Events\BoostCancelled;
use App\Domain\PaidAdvertising\Events\BoostCompleted;
use App\Domain\PaidAdvertising\Events\BoostCreated;
use App\Domain\PaidAdvertising\Events\BoostRejected;
use App\Domain\PaidAdvertising\Exceptions\BoostNotAllowedException;
use App\Domain\PaidAdvertising\Exceptions\InvalidAdStatusTransitionException;
use App\Domain\PaidAdvertising\ValueObjects\AdBudget;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\Shared\ValueObjects\Uuid;

function createTestBoost(AdStatus $status = AdStatus::Draft): AdBoost
{
    $budget = AdBudget::create(5000, 'USD', BudgetType::Daily);
    $orgId = Uuid::generate();
    $userId = Uuid::generate();

    if ($status === AdStatus::Draft) {
        return AdBoost::create(
            organizationId: $orgId,
            scheduledPostId: Uuid::generate(),
            adAccountId: Uuid::generate(),
            audienceId: Uuid::generate(),
            budget: $budget,
            durationDays: 7,
            objective: AdObjective::Reach,
            createdBy: $userId,
        );
    }

    return AdBoost::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        scheduledPostId: Uuid::generate(),
        adAccountId: Uuid::generate(),
        audienceId: Uuid::generate(),
        budget: $budget,
        durationDays: 7,
        objective: AdObjective::Reach,
        status: $status,
        externalIds: $status === AdStatus::Active ? ['campaign_id' => 'camp_1', 'ad_id' => 'ad_1'] : null,
        rejectionReason: $status === AdStatus::Rejected ? 'Policy violation' : null,
        startedAt: in_array($status, [AdStatus::Active, AdStatus::Paused, AdStatus::Completed]) ? new DateTimeImmutable('-1 day') : null,
        completedAt: $status === AdStatus::Completed ? new DateTimeImmutable : null,
        createdBy: $userId,
        createdAt: new DateTimeImmutable('-2 days'),
        updatedAt: new DateTimeImmutable,
    );
}

// ──────────────────────────────────────────────────────────────────
// Creation
// ──────────────────────────────────────────────────────────────────

it('creates boost in draft status with BoostCreated event', function () {
    $boost = createTestBoost();

    expect($boost->status)->toBe(AdStatus::Draft)
        ->and($boost->durationDays)->toBe(7)
        ->and($boost->budget->amountCents)->toBe(5000)
        ->and($boost->externalIds)->toBeNull()
        ->and($boost->rejectionReason)->toBeNull()
        ->and($boost->startedAt)->toBeNull()
        ->and($boost->completedAt)->toBeNull()
        ->and($boost->domainEvents)->toHaveCount(1)
        ->and($boost->domainEvents[0])->toBeInstanceOf(BoostCreated::class);
});

it('rejects duration less than 1 day', function () {
    AdBoost::create(
        organizationId: Uuid::generate(),
        scheduledPostId: Uuid::generate(),
        adAccountId: Uuid::generate(),
        audienceId: Uuid::generate(),
        budget: AdBudget::create(5000, 'USD', BudgetType::Daily),
        durationDays: 0,
        objective: AdObjective::Reach,
        createdBy: Uuid::generate(),
    );
})->throws(BoostNotAllowedException::class);

it('rejects zero budget', function () {
    AdBoost::create(
        organizationId: Uuid::generate(),
        scheduledPostId: Uuid::generate(),
        adAccountId: Uuid::generate(),
        audienceId: Uuid::generate(),
        budget: AdBudget::create(0, 'USD', BudgetType::Daily),
        durationDays: 7,
        objective: AdObjective::Reach,
        createdBy: Uuid::generate(),
    );
})->throws(BoostNotAllowedException::class);

it('reconstitutes boost without events', function () {
    $boost = createTestBoost(AdStatus::Active);

    expect($boost->status)->toBe(AdStatus::Active)
        ->and($boost->externalIds)->not->toBeNull()
        ->and($boost->domainEvents)->toBeEmpty();
});

// ──────────────────────────────────────────────────────────────────
// Valid State Transitions
// ──────────────────────────────────────────────────────────────────

it('submits draft for review', function () {
    $boost = createTestBoost(AdStatus::Draft);
    $submitted = $boost->submitForReview();

    expect($submitted->status)->toBe(AdStatus::PendingReview);
});

it('activates from pending review with external ids and BoostActivated event', function () {
    $boost = createTestBoost(AdStatus::PendingReview);
    $externalIds = ['campaign_id' => 'camp_1', 'adset_id' => 'adset_1', 'ad_id' => 'ad_1'];

    $activated = $boost->activate($externalIds, (string) Uuid::generate());

    expect($activated->status)->toBe(AdStatus::Active)
        ->and($activated->externalIds)->toBe($externalIds)
        ->and($activated->startedAt)->not->toBeNull()
        ->and($activated->domainEvents)->toHaveCount(1)
        ->and($activated->domainEvents[0])->toBeInstanceOf(BoostActivated::class);
});

it('pauses active boost', function () {
    $boost = createTestBoost(AdStatus::Active);
    $paused = $boost->pause((string) Uuid::generate());

    expect($paused->status)->toBe(AdStatus::Paused);
});

it('resumes paused boost', function () {
    $boost = createTestBoost(AdStatus::Paused);
    $resumed = $boost->resume((string) Uuid::generate());

    expect($resumed->status)->toBe(AdStatus::Active);
});

it('completes active boost with spend and BoostCompleted event', function () {
    $boost = createTestBoost(AdStatus::Active);
    $completed = $boost->complete(15000, (string) Uuid::generate());

    expect($completed->status)->toBe(AdStatus::Completed)
        ->and($completed->completedAt)->not->toBeNull()
        ->and($completed->domainEvents)->toHaveCount(1)
        ->and($completed->domainEvents[0])->toBeInstanceOf(BoostCompleted::class);
});

it('completes paused boost', function () {
    $boost = createTestBoost(AdStatus::Paused);
    $completed = $boost->complete(10000, (string) Uuid::generate());

    expect($completed->status)->toBe(AdStatus::Completed);
});

it('rejects pending review boost with reason and BoostRejected event', function () {
    $boost = createTestBoost(AdStatus::PendingReview);
    $rejected = $boost->reject('Policy violation', (string) Uuid::generate());

    expect($rejected->status)->toBe(AdStatus::Rejected)
        ->and($rejected->rejectionReason)->toBe('Policy violation')
        ->and($rejected->domainEvents)->toHaveCount(1)
        ->and($rejected->domainEvents[0])->toBeInstanceOf(BoostRejected::class);
});

it('cancels draft boost with BoostCancelled event', function () {
    $boost = createTestBoost(AdStatus::Draft);
    $cancelled = $boost->cancel((string) Uuid::generate());

    expect($cancelled->status)->toBe(AdStatus::Cancelled)
        ->and($cancelled->domainEvents)->toHaveCount(2)
        ->and($cancelled->domainEvents[1])->toBeInstanceOf(BoostCancelled::class);
});

it('cancels pending_review boost', function () {
    $boost = createTestBoost(AdStatus::PendingReview);
    $cancelled = $boost->cancel((string) Uuid::generate());

    expect($cancelled->status)->toBe(AdStatus::Cancelled);
});

it('cancels active boost', function () {
    $boost = createTestBoost(AdStatus::Active);
    $cancelled = $boost->cancel((string) Uuid::generate());

    expect($cancelled->status)->toBe(AdStatus::Cancelled);
});

it('cancels paused boost', function () {
    $boost = createTestBoost(AdStatus::Paused);
    $cancelled = $boost->cancel((string) Uuid::generate());

    expect($cancelled->status)->toBe(AdStatus::Cancelled);
});

// ──────────────────────────────────────────────────────────────────
// Invalid State Transitions
// ──────────────────────────────────────────────────────────────────

it('throws when submitting non-draft for review', function () {
    $boost = createTestBoost(AdStatus::Active);
    $boost->submitForReview();
})->throws(InvalidAdStatusTransitionException::class);

it('throws when activating from draft', function () {
    $boost = createTestBoost(AdStatus::Draft);
    $boost->activate(['campaign_id' => 'x'], (string) Uuid::generate());
})->throws(InvalidAdStatusTransitionException::class);

it('throws when cancelling completed boost', function () {
    $boost = createTestBoost(AdStatus::Completed);
    $boost->cancel((string) Uuid::generate());
})->throws(BoostNotAllowedException::class);

it('throws when cancelling rejected boost', function () {
    $boost = createTestBoost(AdStatus::Rejected);
    $boost->cancel((string) Uuid::generate());
})->throws(BoostNotAllowedException::class);

// ──────────────────────────────────────────────────────────────────
// Query Methods
// ──────────────────────────────────────────────────────────────────

it('isActive returns true only for active status', function () {
    expect(createTestBoost(AdStatus::Active)->isActive())->toBeTrue()
        ->and(createTestBoost(AdStatus::Draft)->isActive())->toBeFalse()
        ->and(createTestBoost(AdStatus::Paused)->isActive())->toBeFalse();
});

it('isTerminal returns true for completed, rejected, cancelled', function () {
    expect(createTestBoost(AdStatus::Completed)->isTerminal())->toBeTrue()
        ->and(createTestBoost(AdStatus::Rejected)->isTerminal())->toBeTrue()
        ->and(createTestBoost(AdStatus::Cancelled)->isTerminal())->toBeTrue()
        ->and(createTestBoost(AdStatus::Draft)->isTerminal())->toBeFalse()
        ->and(createTestBoost(AdStatus::Active)->isTerminal())->toBeFalse();
});

it('canBeCancelled for non-terminal statuses', function () {
    expect(createTestBoost(AdStatus::Draft)->canBeCancelled())->toBeTrue()
        ->and(createTestBoost(AdStatus::PendingReview)->canBeCancelled())->toBeTrue()
        ->and(createTestBoost(AdStatus::Active)->canBeCancelled())->toBeTrue()
        ->and(createTestBoost(AdStatus::Paused)->canBeCancelled())->toBeTrue()
        ->and(createTestBoost(AdStatus::Completed)->canBeCancelled())->toBeFalse();
});

it('releases events returning clean instance', function () {
    $boost = createTestBoost();

    expect($boost->domainEvents)->toHaveCount(1);

    $released = $boost->releaseEvents();

    expect($released->domainEvents)->toBeEmpty()
        ->and($released->status)->toBe(AdStatus::Draft);
});
