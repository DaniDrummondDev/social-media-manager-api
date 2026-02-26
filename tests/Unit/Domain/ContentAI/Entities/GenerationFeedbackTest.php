<?php

declare(strict_types=1);

use App\Domain\ContentAI\Entities\GenerationFeedback;
use App\Domain\ContentAI\Events\GenerationEdited;
use App\Domain\ContentAI\Events\GenerationFeedbackRecorded;
use App\Domain\ContentAI\Exceptions\InvalidFeedbackException;
use App\Domain\ContentAI\ValueObjects\DiffSummary;
use App\Domain\ContentAI\ValueObjects\FeedbackAction;
use App\Domain\Shared\ValueObjects\Uuid;

function createFeedback(array $overrides = []): GenerationFeedback
{
    return GenerationFeedback::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        userId: $overrides['userId'] ?? Uuid::generate(),
        generationId: $overrides['generationId'] ?? Uuid::generate(),
        action: $overrides['action'] ?? FeedbackAction::Accepted,
        originalOutput: $overrides['originalOutput'] ?? ['title' => 'Original'],
        editedOutput: $overrides['editedOutput'] ?? null,
        contentId: $overrides['contentId'] ?? null,
        generationType: $overrides['generationType'] ?? 'title',
        timeToDecisionMs: $overrides['timeToDecisionMs'] ?? 1500,
    );
}

it('creates accepted feedback with FeedbackRecorded event', function () {
    $feedback = createFeedback(['action' => FeedbackAction::Accepted]);

    expect($feedback->action)->toBe(FeedbackAction::Accepted)
        ->and($feedback->editedOutput)->toBeNull()
        ->and($feedback->diffSummary)->toBeNull()
        ->and($feedback->domainEvents)->toHaveCount(1)
        ->and($feedback->domainEvents[0])->toBeInstanceOf(GenerationFeedbackRecorded::class)
        ->and($feedback->domainEvents[0]->action)->toBe('accepted');
});

it('creates edited feedback with two events', function () {
    $feedback = createFeedback([
        'action' => FeedbackAction::Edited,
        'editedOutput' => ['title' => 'Edited title'],
    ]);

    expect($feedback->action)->toBe(FeedbackAction::Edited)
        ->and($feedback->editedOutput)->toBe(['title' => 'Edited title'])
        ->and($feedback->domainEvents)->toHaveCount(2)
        ->and($feedback->domainEvents[0])->toBeInstanceOf(GenerationFeedbackRecorded::class)
        ->and($feedback->domainEvents[1])->toBeInstanceOf(GenerationEdited::class);
});

it('creates rejected feedback', function () {
    $feedback = createFeedback(['action' => FeedbackAction::Rejected]);

    expect($feedback->action)->toBe(FeedbackAction::Rejected)
        ->and($feedback->editedOutput)->toBeNull()
        ->and($feedback->domainEvents)->toHaveCount(1);
});

it('throws when edited action has no editedOutput', function () {
    createFeedback([
        'action' => FeedbackAction::Edited,
        'editedOutput' => null,
    ]);
})->throws(InvalidFeedbackException::class);

it('clears editedOutput for non-edited actions', function () {
    $feedback = createFeedback([
        'action' => FeedbackAction::Accepted,
        'editedOutput' => ['title' => 'Should be cleared'],
    ]);

    expect($feedback->editedOutput)->toBeNull();
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $now = new DateTimeImmutable;

    $feedback = GenerationFeedback::reconstitute(
        id: $id,
        organizationId: $orgId,
        userId: Uuid::generate(),
        generationId: Uuid::generate(),
        action: FeedbackAction::Accepted,
        originalOutput: ['title' => 'Test'],
        editedOutput: null,
        diffSummary: null,
        contentId: null,
        generationType: 'title',
        timeToDecisionMs: 500,
        createdAt: $now,
    );

    expect($feedback->id)->toEqual($id)
        ->and($feedback->organizationId)->toEqual($orgId)
        ->and($feedback->domainEvents)->toBeEmpty();
});

it('applies withDiffSummary returning new instance', function () {
    $feedback = createFeedback([
        'action' => FeedbackAction::Edited,
        'editedOutput' => ['title' => 'New'],
    ]);

    $diffSummary = DiffSummary::create(
        [['field' => 'title', 'before' => 'Old', 'after' => 'New']],
        0.5,
    );

    $updated = $feedback->withDiffSummary($diffSummary);

    expect($updated->diffSummary)->not->toBeNull()
        ->and($updated->diffSummary->changeRatio)->toBe(0.5)
        ->and($feedback->diffSummary)->toBeNull(); // original unchanged
});

it('provides convenience boolean methods', function () {
    $accepted = createFeedback(['action' => FeedbackAction::Accepted]);
    $edited = createFeedback(['action' => FeedbackAction::Edited, 'editedOutput' => ['t' => 'e']]);
    $rejected = createFeedback(['action' => FeedbackAction::Rejected]);

    expect($accepted->isAccepted())->toBeTrue()
        ->and($accepted->isEdited())->toBeFalse()
        ->and($accepted->isRejected())->toBeFalse()
        ->and($edited->isEdited())->toBeTrue()
        ->and($rejected->isRejected())->toBeTrue();
});
