<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\Comment;
use App\Domain\Engagement\Events\CommentCaptured;
use App\Domain\Engagement\Events\CommentReplied;
use App\Domain\Engagement\Exceptions\CommentAlreadyRepliedException;
use App\Domain\Engagement\ValueObjects\Sentiment;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

function createComment(array $overrides = []): Comment
{
    return Comment::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        contentId: $overrides['contentId'] ?? Uuid::generate(),
        socialAccountId: $overrides['socialAccountId'] ?? Uuid::generate(),
        provider: $overrides['provider'] ?? SocialProvider::Instagram,
        externalCommentId: $overrides['externalCommentId'] ?? 'ext-123',
        authorName: $overrides['authorName'] ?? 'John Doe',
        authorExternalId: $overrides['authorExternalId'] ?? 'author-123',
        authorProfileUrl: $overrides['authorProfileUrl'] ?? null,
        text: $overrides['text'] ?? 'Great post!',
        sentiment: $overrides['sentiment'] ?? Sentiment::Positive,
        sentimentScore: $overrides['sentimentScore'] ?? 0.9,
        isFromOwner: $overrides['isFromOwner'] ?? false,
        commentedAt: $overrides['commentedAt'] ?? new DateTimeImmutable,
    );
}

it('creates with CommentCaptured event', function () {
    $comment = createComment();

    expect($comment->isRead)->toBeFalse()
        ->and($comment->isFromOwner)->toBeFalse()
        ->and($comment->isReplied())->toBeFalse()
        ->and($comment->domainEvents)->toHaveCount(1)
        ->and($comment->domainEvents[0])->toBeInstanceOf(CommentCaptured::class);
});

it('marks as read', function () {
    $comment = createComment();
    $read = $comment->markAsRead();

    expect($read->isRead)->toBeTrue()
        ->and($comment->isRead)->toBeFalse();
});

it('replies with CommentReplied event', function () {
    $comment = createComment();
    $userId = Uuid::generate();
    $replied = $comment->reply('Thank you!', $userId, 'reply-ext-123');

    expect($replied->isReplied())->toBeTrue()
        ->and($replied->replyText)->toBe('Thank you!')
        ->and($replied->repliedBy)->toEqual($userId)
        ->and($replied->repliedByAutomation)->toBeFalse()
        ->and($replied->replyExternalId)->toBe('reply-ext-123')
        ->and($replied->isRead)->toBeTrue()
        ->and($replied->domainEvents)->toHaveCount(2)
        ->and($replied->domainEvents[1])->toBeInstanceOf(CommentReplied::class);
});

it('replies by automation', function () {
    $comment = createComment();
    $ruleId = Uuid::generate();
    $replied = $comment->replyByAutomation('Auto reply', $ruleId);

    expect($replied->isReplied())->toBeTrue()
        ->and($replied->repliedByAutomation)->toBeTrue()
        ->and($replied->repliedBy)->toBeNull()
        ->and($replied->replyText)->toBe('Auto reply');
});

it('throws when replying to already replied comment', function () {
    $comment = createComment();
    $userId = Uuid::generate();
    $replied = $comment->reply('First reply', $userId);

    $replied->reply('Second reply', $userId);
})->throws(CommentAlreadyRepliedException::class);

it('throws when automation replies to already replied comment', function () {
    $comment = createComment();
    $userId = Uuid::generate();
    $replied = $comment->reply('First reply', $userId);

    $replied->replyByAutomation('Auto reply', Uuid::generate());
})->throws(CommentAlreadyRepliedException::class);

it('releases events', function () {
    $comment = createComment();

    expect($comment->domainEvents)->toHaveCount(1);

    $released = $comment->releaseEvents();
    expect($released->domainEvents)->toHaveCount(0);
});
