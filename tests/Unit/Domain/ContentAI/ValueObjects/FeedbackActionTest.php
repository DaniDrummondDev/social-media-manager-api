<?php

declare(strict_types=1);

use App\Domain\ContentAI\ValueObjects\FeedbackAction;

it('has three cases', function () {
    expect(FeedbackAction::cases())->toHaveCount(3)
        ->and(FeedbackAction::Accepted->value)->toBe('accepted')
        ->and(FeedbackAction::Edited->value)->toBe('edited')
        ->and(FeedbackAction::Rejected->value)->toBe('rejected');
});

it('requiresEditedOutput returns true only for Edited', function () {
    expect(FeedbackAction::Edited->requiresEditedOutput())->toBeTrue()
        ->and(FeedbackAction::Accepted->requiresEditedOutput())->toBeFalse()
        ->and(FeedbackAction::Rejected->requiresEditedOutput())->toBeFalse();
});

it('can be created from value', function () {
    expect(FeedbackAction::from('accepted'))->toBe(FeedbackAction::Accepted)
        ->and(FeedbackAction::from('edited'))->toBe(FeedbackAction::Edited)
        ->and(FeedbackAction::from('rejected'))->toBe(FeedbackAction::Rejected);
});

it('tryFrom returns null for invalid value', function () {
    expect(FeedbackAction::tryFrom('invalid'))->toBeNull();
});
