<?php

declare(strict_types=1);

use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\Events\PostCancelled;
use App\Domain\Publishing\Events\PostDispatched;
use App\Domain\Publishing\Events\PostFailed;
use App\Domain\Publishing\Events\PostPublished;
use App\Domain\Publishing\Events\PostScheduled;
use App\Domain\Publishing\Exceptions\InvalidPublishingStatusTransitionException;
use App\Domain\Publishing\Exceptions\PublishingNotAllowedException;
use App\Domain\Publishing\ValueObjects\PublishError;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;

function createPendingPost(): ScheduledPost
{
    return ScheduledPost::create(
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::forFuture(new DateTimeImmutable('+1 hour')),
    );
}

it('creates with pending status and emits PostScheduled', function () {
    $post = createPendingPost();

    expect($post->status)->toBe(PublishingStatus::Pending)
        ->and($post->attempts)->toBe(0)
        ->and($post->maxAttempts)->toBe(3)
        ->and($post->publishedAt)->toBeNull()
        ->and($post->externalPostId)->toBeNull()
        ->and($post->dispatchedAt)->toBeNull()
        ->and($post->domainEvents)->toHaveCount(1)
        ->and($post->domainEvents[0])->toBeInstanceOf(PostScheduled::class);
});

it('creates for immediate publish with dispatched status', function () {
    $post = ScheduledPost::createForImmediatePublish(
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
    );

    expect($post->status)->toBe(PublishingStatus::Dispatched)
        ->and($post->dispatchedAt)->not->toBeNull()
        ->and($post->domainEvents)->toHaveCount(2)
        ->and($post->domainEvents[0])->toBeInstanceOf(PostScheduled::class)
        ->and($post->domainEvents[1])->toBeInstanceOf(PostDispatched::class);
});

it('reconstitutes without events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $post = ScheduledPost::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::fromDateTimeImmutable($now),
        status: PublishingStatus::Published,
        publishedAt: $now,
        externalPostId: 'ext-123',
        externalPostUrl: 'https://instagram.com/p/123',
        attempts: 1,
        maxAttempts: 3,
        lastAttemptedAt: $now,
        lastError: null,
        nextRetryAt: null,
        dispatchedAt: $now,
        createdAt: $now,
        updatedAt: $now,
    );

    expect((string) $post->id)->toBe((string) $id)
        ->and($post->status)->toBe(PublishingStatus::Published)
        ->and($post->externalPostId)->toBe('ext-123')
        ->and($post->domainEvents)->toBeEmpty();
});

it('transitions to dispatched', function () {
    $post = createPendingPost()->releaseEvents();
    $dispatched = $post->markAsDispatched();

    expect($dispatched->status)->toBe(PublishingStatus::Dispatched)
        ->and($dispatched->dispatchedAt)->not->toBeNull()
        ->and($dispatched->domainEvents)->toHaveCount(1)
        ->and($dispatched->domainEvents[0])->toBeInstanceOf(PostDispatched::class);
});

it('transitions to publishing', function () {
    $post = createPendingPost()->releaseEvents()->markAsDispatched()->releaseEvents();
    $publishing = $post->markAsPublishing();

    expect($publishing->status)->toBe(PublishingStatus::Publishing)
        ->and($publishing->attempts)->toBe(1)
        ->and($publishing->lastAttemptedAt)->not->toBeNull();
});

it('transitions to published', function () {
    $post = createPendingPost()->releaseEvents()
        ->markAsDispatched()->releaseEvents()
        ->markAsPublishing()->releaseEvents();

    $published = $post->markAsPublished('ext-456', 'https://instagram.com/p/456');

    expect($published->status)->toBe(PublishingStatus::Published)
        ->and($published->publishedAt)->not->toBeNull()
        ->and($published->externalPostId)->toBe('ext-456')
        ->and($published->externalPostUrl)->toBe('https://instagram.com/p/456')
        ->and($published->domainEvents)->toHaveCount(1)
        ->and($published->domainEvents[0])->toBeInstanceOf(PostPublished::class);
});

it('transitions to failed with backoff calculation', function () {
    $post = createPendingPost()->releaseEvents()
        ->markAsDispatched()->releaseEvents()
        ->markAsPublishing()->releaseEvents();

    $error = new PublishError('RATE_LIMITED', 'Too many requests', false);
    $failed = $post->markAsFailed($error);

    expect($failed->status)->toBe(PublishingStatus::Failed)
        ->and($failed->lastError)->toBe($error)
        ->and($failed->nextRetryAt)->not->toBeNull()
        ->and($failed->domainEvents)->toHaveCount(1)
        ->and($failed->domainEvents[0])->toBeInstanceOf(PostFailed::class);
});

it('does not set next retry for permanent errors', function () {
    $post = createPendingPost()->releaseEvents()
        ->markAsDispatched()->releaseEvents()
        ->markAsPublishing()->releaseEvents();

    $error = new PublishError('INVALID_TOKEN', 'Permanently invalid', true);
    $failed = $post->markAsFailed($error);

    expect($failed->nextRetryAt)->toBeNull();
});

it('does not set next retry when max attempts reached', function () {
    $post = ScheduledPost::create(
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::forFuture(new DateTimeImmutable('+1 hour')),
        maxAttempts: 1,
    )->releaseEvents()
        ->markAsDispatched()->releaseEvents()
        ->markAsPublishing()->releaseEvents();

    $error = new PublishError('TIMEOUT', 'Connection timed out', false);
    $failed = $post->markAsFailed($error);

    expect($failed->nextRetryAt)->toBeNull();
});

it('cancels a pending post', function () {
    $post = createPendingPost()->releaseEvents();
    $cancelled = $post->cancel();

    expect($cancelled->status)->toBe(PublishingStatus::Cancelled)
        ->and($cancelled->domainEvents)->toHaveCount(1)
        ->and($cancelled->domainEvents[0])->toBeInstanceOf(PostCancelled::class);
});

it('rejects cancel on non-pending post', function () {
    $post = createPendingPost()->releaseEvents()->markAsDispatched()->releaseEvents();

    $post->cancel();
})->throws(PublishingNotAllowedException::class);

it('reschedules a pending post', function () {
    $post = createPendingPost()->releaseEvents();
    $newTime = ScheduleTime::forFuture(new DateTimeImmutable('+2 hours'));

    $rescheduled = $post->reschedule($newTime);

    expect($rescheduled->scheduledAt)->toBe($newTime)
        ->and($rescheduled->status)->toBe(PublishingStatus::Pending);
});

it('rejects reschedule on dispatched post', function () {
    $post = createPendingPost()->releaseEvents()->markAsDispatched()->releaseEvents();
    $newTime = ScheduleTime::forFuture(new DateTimeImmutable('+2 hours'));

    $post->reschedule($newTime);
})->throws(PublishingNotAllowedException::class);

it('retries a failed retryable post', function () {
    $post = createPendingPost()->releaseEvents()
        ->markAsDispatched()->releaseEvents()
        ->markAsPublishing()->releaseEvents()
        ->markAsFailed(new PublishError('TIMEOUT', 'Timed out', false))->releaseEvents();

    $retried = $post->retryNow();

    expect($retried->status)->toBe(PublishingStatus::Publishing)
        ->and($retried->attempts)->toBe(2)
        ->and($retried->domainEvents)->toHaveCount(1)
        ->and($retried->domainEvents[0])->toBeInstanceOf(PostDispatched::class);
});

it('rejects retry on permanent error', function () {
    $post = createPendingPost()->releaseEvents()
        ->markAsDispatched()->releaseEvents()
        ->markAsPublishing()->releaseEvents()
        ->markAsFailed(new PublishError('BANNED', 'Account banned', true))->releaseEvents();

    $post->retryNow();
})->throws(PublishingNotAllowedException::class);

it('identifies retryable state correctly', function () {
    $post = createPendingPost()->releaseEvents()
        ->markAsDispatched()->releaseEvents()
        ->markAsPublishing()->releaseEvents()
        ->markAsFailed(new PublishError('TIMEOUT', 'Timed out', false));

    expect($post->isRetryable())->toBeTrue()
        ->and($post->isAlreadyPublished())->toBeFalse()
        ->and($post->isCancelled())->toBeFalse()
        ->and($post->canBeCancelled())->toBeFalse()
        ->and($post->canBeRescheduled())->toBeFalse();
});

it('rejects invalid status transitions', function () {
    $post = createPendingPost()->releaseEvents();

    $post->markAsPublishing();
})->throws(InvalidPublishingStatusTransitionException::class);

it('releases events', function () {
    $post = createPendingPost();

    expect($post->domainEvents)->toHaveCount(1);

    $released = $post->releaseEvents();

    expect($released->domainEvents)->toBeEmpty()
        ->and((string) $released->id)->toBe((string) $post->id);
});
