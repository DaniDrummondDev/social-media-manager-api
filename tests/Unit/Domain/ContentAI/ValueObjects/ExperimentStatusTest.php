<?php

declare(strict_types=1);

use App\Domain\ContentAI\ValueObjects\ExperimentStatus;

it('has four cases', function () {
    expect(ExperimentStatus::cases())->toHaveCount(4)
        ->and(ExperimentStatus::Draft->value)->toBe('draft')
        ->and(ExperimentStatus::Running->value)->toBe('running')
        ->and(ExperimentStatus::Completed->value)->toBe('completed')
        ->and(ExperimentStatus::Canceled->value)->toBe('canceled');
});

it('allows Draft to transition to Running and Canceled', function () {
    expect(ExperimentStatus::Draft->canTransitionTo(ExperimentStatus::Running))->toBeTrue()
        ->and(ExperimentStatus::Draft->canTransitionTo(ExperimentStatus::Canceled))->toBeTrue()
        ->and(ExperimentStatus::Draft->canTransitionTo(ExperimentStatus::Completed))->toBeFalse()
        ->and(ExperimentStatus::Draft->canTransitionTo(ExperimentStatus::Draft))->toBeFalse();
});

it('allows Running to transition to Completed and Canceled', function () {
    expect(ExperimentStatus::Running->canTransitionTo(ExperimentStatus::Completed))->toBeTrue()
        ->and(ExperimentStatus::Running->canTransitionTo(ExperimentStatus::Canceled))->toBeTrue()
        ->and(ExperimentStatus::Running->canTransitionTo(ExperimentStatus::Draft))->toBeFalse()
        ->and(ExperimentStatus::Running->canTransitionTo(ExperimentStatus::Running))->toBeFalse();
});

it('does not allow final states to transition', function () {
    foreach ([ExperimentStatus::Completed, ExperimentStatus::Canceled] as $status) {
        foreach (ExperimentStatus::cases() as $target) {
            expect($status->canTransitionTo($target))->toBeFalse();
        }
    }
});

it('identifies final states', function () {
    expect(ExperimentStatus::Completed->isFinal())->toBeTrue()
        ->and(ExperimentStatus::Canceled->isFinal())->toBeTrue()
        ->and(ExperimentStatus::Draft->isFinal())->toBeFalse()
        ->and(ExperimentStatus::Running->isFinal())->toBeFalse();
});
