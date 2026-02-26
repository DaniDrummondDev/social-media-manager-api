<?php

declare(strict_types=1);

use App\Domain\SocialListening\ValueObjects\QueryStatus;

it('allows active to paused transition', function () {
    expect(QueryStatus::Active->canTransitionTo(QueryStatus::Paused))->toBeTrue();
});

it('allows active to deleted transition', function () {
    expect(QueryStatus::Active->canTransitionTo(QueryStatus::Deleted))->toBeTrue();
});

it('allows paused to active transition', function () {
    expect(QueryStatus::Paused->canTransitionTo(QueryStatus::Active))->toBeTrue();
});

it('allows paused to deleted transition', function () {
    expect(QueryStatus::Paused->canTransitionTo(QueryStatus::Deleted))->toBeTrue();
});

it('does not allow deleted to any transition', function () {
    expect(QueryStatus::Deleted->canTransitionTo(QueryStatus::Active))->toBeFalse()
        ->and(QueryStatus::Deleted->canTransitionTo(QueryStatus::Paused))->toBeFalse()
        ->and(QueryStatus::Deleted->canTransitionTo(QueryStatus::Deleted))->toBeFalse();
});
