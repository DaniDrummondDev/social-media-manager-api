<?php

declare(strict_types=1);

use App\Domain\ContentAI\Entities\PromptExperiment;
use App\Domain\ContentAI\Events\PromptExperimentCompleted;
use App\Domain\ContentAI\Events\PromptExperimentStarted;
use App\Domain\ContentAI\Exceptions\InvalidExperimentStatusTransitionException;
use App\Domain\ContentAI\ValueObjects\ExperimentStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createExperiment(array $overrides = []): PromptExperiment
{
    return PromptExperiment::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        generationType: $overrides['generationType'] ?? 'title',
        name: $overrides['name'] ?? 'A/B Test Title',
        variantAId: $overrides['variantAId'] ?? Uuid::generate(),
        variantBId: $overrides['variantBId'] ?? Uuid::generate(),
        trafficSplit: $overrides['trafficSplit'] ?? 0.5,
        minSampleSize: $overrides['minSampleSize'] ?? 50,
        userId: $overrides['userId'] ?? 'user-1',
    );
}

it('creates in Draft status with zero counters', function () {
    $experiment = createExperiment();

    expect($experiment->status)->toBe(ExperimentStatus::Draft)
        ->and($experiment->variantAUses)->toBe(0)
        ->and($experiment->variantAAccepted)->toBe(0)
        ->and($experiment->variantBUses)->toBe(0)
        ->and($experiment->variantBAccepted)->toBe(0)
        ->and($experiment->winnerId)->toBeNull()
        ->and($experiment->confidenceLevel)->toBeNull()
        ->and($experiment->startedAt)->toBeNull()
        ->and($experiment->completedAt)->toBeNull()
        ->and($experiment->domainEvents)->toBeEmpty();
});

it('starts experiment with PromptExperimentStarted event', function () {
    $experiment = createExperiment();
    $started = $experiment->start('user-1');

    expect($started->status)->toBe(ExperimentStatus::Running)
        ->and($started->startedAt)->not->toBeNull()
        ->and($started->domainEvents)->toHaveCount(1)
        ->and($started->domainEvents[0])->toBeInstanceOf(PromptExperimentStarted::class);
});

it('throws when starting a running experiment', function () {
    $experiment = createExperiment();
    $started = $experiment->start('user-1');
    $started->start('user-1');
})->throws(InvalidExperimentStatusTransitionException::class);

it('records variant A usage when running', function () {
    $experiment = createExperiment();
    $running = $experiment->start('user-1');
    $updated = $running->recordVariantUsage(isVariantA: true, accepted: true);

    expect($updated->variantAUses)->toBe(1)
        ->and($updated->variantAAccepted)->toBe(1)
        ->and($updated->variantBUses)->toBe(0);
});

it('records variant B usage when running', function () {
    $experiment = createExperiment();
    $running = $experiment->start('user-1');
    $updated = $running->recordVariantUsage(isVariantA: false, accepted: false);

    expect($updated->variantBUses)->toBe(1)
        ->and($updated->variantBAccepted)->toBe(0)
        ->and($updated->variantAUses)->toBe(0);
});

it('ignores variant usage when not running', function () {
    $experiment = createExperiment(); // Draft
    $same = $experiment->recordVariantUsage(isVariantA: true, accepted: true);

    expect($same->variantAUses)->toBe(0);
});

it('hasMinimumSamples checks both variants', function () {
    $experiment = createExperiment(['minSampleSize' => 2]);
    $running = $experiment->start('user-1');

    $step1 = $running->recordVariantUsage(true, true);
    $step2 = $step1->recordVariantUsage(true, true);
    $step3 = $step2->recordVariantUsage(false, true);

    expect($step3->hasMinimumSamples())->toBeFalse();

    $step4 = $step3->recordVariantUsage(false, false);
    expect($step4->hasMinimumSamples())->toBeTrue();
});

it('calculateConfidence returns null without minimum samples', function () {
    $experiment = createExperiment();
    $running = $experiment->start('user-1');

    expect($running->calculateConfidence())->toBeNull();
});

it('evaluates with sufficient data and high confidence', function () {
    $orgId = Uuid::generate();
    $variantA = Uuid::generate();
    $variantB = Uuid::generate();
    $now = new DateTimeImmutable;

    // Variant A: 80/100 = 80%, Variant B: 40/100 = 40% → high confidence
    $experiment = PromptExperiment::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        generationType: 'title',
        name: 'Test',
        status: ExperimentStatus::Running,
        variantAId: $variantA,
        variantBId: $variantB,
        trafficSplit: 0.5,
        minSampleSize: 50,
        variantAUses: 100,
        variantAAccepted: 80,
        variantBUses: 100,
        variantBAccepted: 40,
        winnerId: null,
        confidenceLevel: null,
        startedAt: $now,
        completedAt: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $evaluated = $experiment->evaluate('user-1');

    expect($evaluated->status)->toBe(ExperimentStatus::Completed)
        ->and($evaluated->winnerId)->toEqual($variantA)
        ->and($evaluated->confidenceLevel)->toBeGreaterThanOrEqual(0.95)
        ->and($evaluated->completedAt)->not->toBeNull()
        ->and($evaluated->domainEvents)->toHaveCount(1)
        ->and($evaluated->domainEvents[0])->toBeInstanceOf(PromptExperimentCompleted::class);
});

it('does not evaluate with insufficient confidence', function () {
    $now = new DateTimeImmutable;

    // Almost identical rates → low confidence
    $experiment = PromptExperiment::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        name: 'Test',
        status: ExperimentStatus::Running,
        variantAId: Uuid::generate(),
        variantBId: Uuid::generate(),
        trafficSplit: 0.5,
        minSampleSize: 50,
        variantAUses: 50,
        variantAAccepted: 25,
        variantBUses: 50,
        variantBAccepted: 24,
        winnerId: null,
        confidenceLevel: null,
        startedAt: $now,
        completedAt: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $same = $experiment->evaluate('user-1');

    expect($same->status)->toBe(ExperimentStatus::Running)
        ->and($same->winnerId)->toBeNull();
});

it('cancels from Draft', function () {
    $experiment = createExperiment();
    $canceled = $experiment->cancel();

    expect($canceled->status)->toBe(ExperimentStatus::Canceled)
        ->and($canceled->completedAt)->not->toBeNull();
});

it('cancels from Running', function () {
    $experiment = createExperiment();
    $running = $experiment->start('user-1');
    $canceled = $running->cancel();

    expect($canceled->status)->toBe(ExperimentStatus::Canceled);
});

it('throws when canceling a completed experiment', function () {
    $now = new DateTimeImmutable;

    $completed = PromptExperiment::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        name: 'Test',
        status: ExperimentStatus::Completed,
        variantAId: Uuid::generate(),
        variantBId: Uuid::generate(),
        trafficSplit: 0.5,
        minSampleSize: 50,
        variantAUses: 100,
        variantAAccepted: 80,
        variantBUses: 100,
        variantBAccepted: 40,
        winnerId: Uuid::generate(),
        confidenceLevel: 0.99,
        startedAt: $now,
        completedAt: $now,
        createdAt: $now,
        updatedAt: $now,
    );

    $completed->cancel();
})->throws(InvalidExperimentStatusTransitionException::class);

it('calculates acceptance rates', function () {
    $now = new DateTimeImmutable;

    $experiment = PromptExperiment::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        name: 'Test',
        status: ExperimentStatus::Running,
        variantAId: Uuid::generate(),
        variantBId: Uuid::generate(),
        trafficSplit: 0.5,
        minSampleSize: 50,
        variantAUses: 10,
        variantAAccepted: 8,
        variantBUses: 10,
        variantBAccepted: 6,
        winnerId: null,
        confidenceLevel: null,
        startedAt: $now,
        completedAt: null,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($experiment->getAcceptanceRateA())->toBe(80.0)
        ->and($experiment->getAcceptanceRateB())->toBe(60.0);
});

it('returns zero acceptance rate for zero uses', function () {
    $experiment = createExperiment();

    expect($experiment->getAcceptanceRateA())->toBe(0.0)
        ->and($experiment->getAcceptanceRateB())->toBe(0.0);
});

it('reconstitutes without events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $experiment = PromptExperiment::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        generationType: 'title',
        name: 'Test',
        status: ExperimentStatus::Draft,
        variantAId: Uuid::generate(),
        variantBId: Uuid::generate(),
        trafficSplit: 0.5,
        minSampleSize: 50,
        variantAUses: 0,
        variantAAccepted: 0,
        variantBUses: 0,
        variantBAccepted: 0,
        winnerId: null,
        confidenceLevel: null,
        startedAt: null,
        completedAt: null,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($experiment->id)->toEqual($id)
        ->and($experiment->domainEvents)->toBeEmpty();
});
