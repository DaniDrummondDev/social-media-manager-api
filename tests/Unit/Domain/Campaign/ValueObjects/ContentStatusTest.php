<?php

declare(strict_types=1);

use App\Domain\Campaign\ValueObjects\ContentStatus;

it('allows draft to transition to ready', function () {
    expect(ContentStatus::Draft->canTransitionTo(ContentStatus::Ready))->toBeTrue();
});

it('allows ready to transition to draft', function () {
    expect(ContentStatus::Ready->canTransitionTo(ContentStatus::Draft))->toBeTrue();
});

it('allows ready to transition to scheduled', function () {
    expect(ContentStatus::Ready->canTransitionTo(ContentStatus::Scheduled))->toBeTrue();
});

it('allows scheduled to transition to published', function () {
    expect(ContentStatus::Scheduled->canTransitionTo(ContentStatus::Published))->toBeTrue();
});

it('does not allow draft to transition to published', function () {
    expect(ContentStatus::Draft->canTransitionTo(ContentStatus::Published))->toBeFalse();
});

it('does not allow published to transition to any status', function () {
    expect(ContentStatus::Published->canTransitionTo(ContentStatus::Draft))->toBeFalse()
        ->and(ContentStatus::Published->canTransitionTo(ContentStatus::Ready))->toBeFalse()
        ->and(ContentStatus::Published->canTransitionTo(ContentStatus::Scheduled))->toBeFalse();
});

it('returns editable only for draft', function () {
    expect(ContentStatus::Draft->isEditable())->toBeTrue()
        ->and(ContentStatus::Ready->isEditable())->toBeFalse()
        ->and(ContentStatus::Scheduled->isEditable())->toBeFalse()
        ->and(ContentStatus::Published->isEditable())->toBeFalse();
});
