<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\ProfileStatus;

it('has correct string values', function () {
    expect(ProfileStatus::Generating->value)->toBe('generating')
        ->and(ProfileStatus::Generated->value)->toBe('generated')
        ->and(ProfileStatus::Expired->value)->toBe('expired');
});

it('allows Generating to transition to Generated', function () {
    expect(ProfileStatus::Generating->canTransitionTo(ProfileStatus::Generated))->toBeTrue();
});

it('does not allow Generating to transition to Expired', function () {
    expect(ProfileStatus::Generating->canTransitionTo(ProfileStatus::Expired))->toBeFalse();
});

it('allows Generated to transition to Expired', function () {
    expect(ProfileStatus::Generated->canTransitionTo(ProfileStatus::Expired))->toBeTrue();
});

it('does not allow Generated to transition to Generating', function () {
    expect(ProfileStatus::Generated->canTransitionTo(ProfileStatus::Generating))->toBeFalse();
});

it('does not allow Expired to transition to any state', function () {
    expect(ProfileStatus::Expired->canTransitionTo(ProfileStatus::Generating))->toBeFalse()
        ->and(ProfileStatus::Expired->canTransitionTo(ProfileStatus::Generated))->toBeFalse()
        ->and(ProfileStatus::Expired->canTransitionTo(ProfileStatus::Expired))->toBeFalse();
});

it('marks only Expired as final', function () {
    expect(ProfileStatus::Generating->isFinal())->toBeFalse()
        ->and(ProfileStatus::Generated->isFinal())->toBeFalse()
        ->and(ProfileStatus::Expired->isFinal())->toBeTrue();
});
