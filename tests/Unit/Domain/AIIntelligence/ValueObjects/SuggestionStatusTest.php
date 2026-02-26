<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\SuggestionStatus;

it('isFinal returns false for Generating', function () {
    expect(SuggestionStatus::Generating->isFinal())->toBeFalse();
});

it('isFinal returns false for Generated', function () {
    expect(SuggestionStatus::Generated->isFinal())->toBeFalse();
});

it('isFinal returns false for Reviewed', function () {
    expect(SuggestionStatus::Reviewed->isFinal())->toBeFalse();
});

it('isFinal returns true for Accepted', function () {
    expect(SuggestionStatus::Accepted->isFinal())->toBeTrue();
});

it('isFinal returns true for Expired', function () {
    expect(SuggestionStatus::Expired->isFinal())->toBeTrue();
});

it('canTransitionTo — Generating to Generated is true', function () {
    expect(SuggestionStatus::Generating->canTransitionTo(SuggestionStatus::Generated))->toBeTrue();
});

it('canTransitionTo — Generating to Accepted is false', function () {
    expect(SuggestionStatus::Generating->canTransitionTo(SuggestionStatus::Accepted))->toBeFalse();
});

it('canTransitionTo — Generated to Reviewed, Accepted, Expired is true', function () {
    expect(SuggestionStatus::Generated->canTransitionTo(SuggestionStatus::Reviewed))->toBeTrue()
        ->and(SuggestionStatus::Generated->canTransitionTo(SuggestionStatus::Accepted))->toBeTrue()
        ->and(SuggestionStatus::Generated->canTransitionTo(SuggestionStatus::Expired))->toBeTrue();
});

it('canTransitionTo — Reviewed to Accepted and Expired is true', function () {
    expect(SuggestionStatus::Reviewed->canTransitionTo(SuggestionStatus::Accepted))->toBeTrue()
        ->and(SuggestionStatus::Reviewed->canTransitionTo(SuggestionStatus::Expired))->toBeTrue();
});

it('canTransitionTo — Accepted to any is false', function () {
    expect(SuggestionStatus::Accepted->canTransitionTo(SuggestionStatus::Generating))->toBeFalse()
        ->and(SuggestionStatus::Accepted->canTransitionTo(SuggestionStatus::Generated))->toBeFalse()
        ->and(SuggestionStatus::Accepted->canTransitionTo(SuggestionStatus::Reviewed))->toBeFalse()
        ->and(SuggestionStatus::Accepted->canTransitionTo(SuggestionStatus::Expired))->toBeFalse();
});

it('canTransitionTo — Expired to any is false', function () {
    expect(SuggestionStatus::Expired->canTransitionTo(SuggestionStatus::Generating))->toBeFalse()
        ->and(SuggestionStatus::Expired->canTransitionTo(SuggestionStatus::Generated))->toBeFalse()
        ->and(SuggestionStatus::Expired->canTransitionTo(SuggestionStatus::Reviewed))->toBeFalse()
        ->and(SuggestionStatus::Expired->canTransitionTo(SuggestionStatus::Accepted))->toBeFalse();
});
