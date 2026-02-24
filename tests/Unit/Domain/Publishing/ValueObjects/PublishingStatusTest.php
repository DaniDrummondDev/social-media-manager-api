<?php

declare(strict_types=1);

use App\Domain\Publishing\ValueObjects\PublishingStatus;

it('allows valid transitions from pending', function () {
    $pending = PublishingStatus::Pending;

    expect($pending->canTransitionTo(PublishingStatus::Dispatched))->toBeTrue()
        ->and($pending->canTransitionTo(PublishingStatus::Cancelled))->toBeTrue()
        ->and($pending->canTransitionTo(PublishingStatus::Publishing))->toBeFalse()
        ->and($pending->canTransitionTo(PublishingStatus::Published))->toBeFalse()
        ->and($pending->canTransitionTo(PublishingStatus::Failed))->toBeFalse();
});

it('allows valid transitions from dispatched', function () {
    $dispatched = PublishingStatus::Dispatched;

    expect($dispatched->canTransitionTo(PublishingStatus::Publishing))->toBeTrue()
        ->and($dispatched->canTransitionTo(PublishingStatus::Pending))->toBeFalse()
        ->and($dispatched->canTransitionTo(PublishingStatus::Published))->toBeFalse();
});

it('allows valid transitions from publishing', function () {
    $publishing = PublishingStatus::Publishing;

    expect($publishing->canTransitionTo(PublishingStatus::Published))->toBeTrue()
        ->and($publishing->canTransitionTo(PublishingStatus::Failed))->toBeTrue()
        ->and($publishing->canTransitionTo(PublishingStatus::Pending))->toBeFalse();
});

it('allows retry transition from failed to publishing', function () {
    $failed = PublishingStatus::Failed;

    expect($failed->canTransitionTo(PublishingStatus::Publishing))->toBeTrue()
        ->and($failed->canTransitionTo(PublishingStatus::Pending))->toBeFalse()
        ->and($failed->canTransitionTo(PublishingStatus::Published))->toBeFalse();
});

it('blocks transitions from terminal states', function () {
    expect(PublishingStatus::Published->canTransitionTo(PublishingStatus::Pending))->toBeFalse()
        ->and(PublishingStatus::Cancelled->canTransitionTo(PublishingStatus::Pending))->toBeFalse();
});

it('identifies terminal states', function () {
    expect(PublishingStatus::Published->isTerminal())->toBeTrue()
        ->and(PublishingStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(PublishingStatus::Pending->isTerminal())->toBeFalse()
        ->and(PublishingStatus::Failed->isTerminal())->toBeFalse();
});

it('identifies active states', function () {
    expect(PublishingStatus::Pending->isActive())->toBeTrue()
        ->and(PublishingStatus::Dispatched->isActive())->toBeTrue()
        ->and(PublishingStatus::Publishing->isActive())->toBeTrue()
        ->and(PublishingStatus::Published->isActive())->toBeFalse()
        ->and(PublishingStatus::Failed->isActive())->toBeFalse()
        ->and(PublishingStatus::Cancelled->isActive())->toBeFalse();
});
