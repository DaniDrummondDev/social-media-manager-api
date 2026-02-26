<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\GapAnalysisStatus;

it('has all expected cases', function () {
    expect(GapAnalysisStatus::cases())->toHaveCount(3);
});

it('creates from string value', function () {
    expect(GapAnalysisStatus::from('generating'))->toBe(GapAnalysisStatus::Generating)
        ->and(GapAnalysisStatus::from('generated'))->toBe(GapAnalysisStatus::Generated)
        ->and(GapAnalysisStatus::from('expired'))->toBe(GapAnalysisStatus::Expired);
});

it('allows Generating to transition to Generated', function () {
    expect(GapAnalysisStatus::Generating->canTransitionTo(GapAnalysisStatus::Generated))->toBeTrue();
});

it('does not allow Generating to transition to Expired', function () {
    expect(GapAnalysisStatus::Generating->canTransitionTo(GapAnalysisStatus::Expired))->toBeFalse();
});

it('allows Generated to transition to Expired', function () {
    expect(GapAnalysisStatus::Generated->canTransitionTo(GapAnalysisStatus::Expired))->toBeTrue();
});

it('does not allow Generated to transition to Generating', function () {
    expect(GapAnalysisStatus::Generated->canTransitionTo(GapAnalysisStatus::Generating))->toBeFalse();
});

it('does not allow Expired to transition to any state', function () {
    expect(GapAnalysisStatus::Expired->canTransitionTo(GapAnalysisStatus::Generating))->toBeFalse()
        ->and(GapAnalysisStatus::Expired->canTransitionTo(GapAnalysisStatus::Generated))->toBeFalse();
});

it('only Expired is final', function () {
    expect(GapAnalysisStatus::Expired->isFinal())->toBeTrue()
        ->and(GapAnalysisStatus::Generating->isFinal())->toBeFalse()
        ->and(GapAnalysisStatus::Generated->isFinal())->toBeFalse();
});
