<?php

declare(strict_types=1);

use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\Events\ContentCreated;
use App\Domain\Campaign\Events\ContentDeleted;
use App\Domain\Campaign\Events\ContentUpdated;
use App\Domain\Campaign\Exceptions\InvalidStatusTransitionException;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Shared\ValueObjects\Uuid;

function createContent(): Content
{
    return Content::create(
        organizationId: Uuid::generate(),
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'Test Content',
        body: 'Content body text',
        hashtags: ['test', 'content'],
    );
}

it('creates content with draft status and event', function () {
    $content = createContent();

    expect($content->status)->toBe(ContentStatus::Draft)
        ->and($content->title)->toBe('Test Content')
        ->and($content->body)->toBe('Content body text')
        ->and($content->hashtags)->toBe(['test', 'content'])
        ->and($content->deletedAt)->toBeNull()
        ->and($content->domainEvents)->toHaveCount(1)
        ->and($content->domainEvents[0])->toBeInstanceOf(ContentCreated::class);
});

it('reconstitutes without events', function () {
    $now = new DateTimeImmutable;
    $content = Content::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'Test',
        body: null,
        hashtags: [],
        status: ContentStatus::Ready,
        aiGenerationId: null,
        createdAt: $now,
        updatedAt: $now,
        deletedAt: null,
        purgeAt: null,
    );

    expect($content->domainEvents)->toBeEmpty()
        ->and($content->status)->toBe(ContentStatus::Ready);
});

it('updates content and emits event', function () {
    $content = createContent();
    $updated = $content->update(title: 'Updated Title', hashtags: ['new']);

    expect($updated->title)->toBe('Updated Title')
        ->and($updated->hashtags)->toBe(['new'])
        ->and($updated->body)->toBe('Content body text')
        ->and($updated->domainEvents)->toHaveCount(2)
        ->and($updated->domainEvents[1])->toBeInstanceOf(ContentUpdated::class);
});

it('transitions from draft to ready', function () {
    $content = createContent();
    $ready = $content->transitionTo(ContentStatus::Ready);

    expect($ready->status)->toBe(ContentStatus::Ready);
});

it('transitions from ready to scheduled', function () {
    $content = createContent();
    $ready = $content->transitionTo(ContentStatus::Ready);
    $scheduled = $ready->transitionTo(ContentStatus::Scheduled);

    expect($scheduled->status)->toBe(ContentStatus::Scheduled);
});

it('rejects invalid status transition', function () {
    $content = createContent();
    $content->transitionTo(ContentStatus::Published);
})->throws(InvalidStatusTransitionException::class);

it('soft deletes with event', function () {
    $content = createContent();
    $deleted = $content->softDelete();

    expect($deleted->isDeleted())->toBeTrue()
        ->and($deleted->deletedAt)->not->toBeNull()
        ->and($deleted->purgeAt)->not->toBeNull()
        ->and($deleted->domainEvents)->toHaveCount(2)
        ->and($deleted->domainEvents[1])->toBeInstanceOf(ContentDeleted::class);
});

it('restores a soft deleted content', function () {
    $content = createContent();
    $deleted = $content->softDelete();
    $restored = $deleted->restore();

    expect($restored->isDeleted())->toBeFalse()
        ->and($restored->deletedAt)->toBeNull()
        ->and($restored->purgeAt)->toBeNull();
});

it('releases events', function () {
    $content = createContent();
    expect($content->domainEvents)->toHaveCount(1);

    $released = $content->releaseEvents();
    expect($released->domainEvents)->toBeEmpty();
});
