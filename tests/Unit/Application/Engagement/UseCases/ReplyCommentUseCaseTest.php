<?php

declare(strict_types=1);

use App\Application\Engagement\Contracts\SocialEngagementFactoryInterface;
use App\Application\Engagement\DTOs\ReplyCommentInput;
use App\Application\Engagement\Exceptions\CommentNotFoundException;
use App\Application\Engagement\UseCases\ReplyCommentUseCase;
use App\Domain\Engagement\Entities\Comment;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Engagement\ValueObjects\Sentiment;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Contracts\SocialEngagementInterface;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

it('replies to a comment successfully', function () {
    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $commentId = Uuid::generate();

    $comment = Comment::reconstitute(
        id: $commentId,
        organizationId: $orgId,
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-1',
        authorName: 'User',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'Great product!',
        sentiment: Sentiment::Positive,
        sentimentScore: 0.9,
        isRead: false,
        isFromOwner: false,
        repliedAt: null,
        repliedBy: null,
        repliedByAutomation: false,
        replyText: null,
        replyExternalId: null,
        commentedAt: new DateTimeImmutable,
        capturedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $repo = Mockery::mock(CommentRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($comment);
    $repo->shouldReceive('update')->once();

    $adapter = Mockery::mock(SocialEngagementInterface::class);
    $adapter->shouldReceive('replyToComment')->once()->andReturn(['id' => 'reply-ext-1']);

    $factory = Mockery::mock(SocialEngagementFactoryInterface::class);
    $factory->shouldReceive('make')->once()->andReturn($adapter);

    $useCase = new ReplyCommentUseCase($repo, $factory);
    $output = $useCase->execute(new ReplyCommentInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        commentId: (string) $commentId,
        text: 'Thanks for the feedback!',
    ));

    expect($output->replyText)->toBe('Thanks for the feedback!')
        ->and($output->repliedAt)->not->toBeNull()
        ->and($output->replyExternalId)->toBe('reply-ext-1');
});

it('throws when comment not found', function () {
    $repo = Mockery::mock(CommentRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $factory = Mockery::mock(SocialEngagementFactoryInterface::class);

    $useCase = new ReplyCommentUseCase($repo, $factory);

    $useCase->execute(new ReplyCommentInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        commentId: (string) Uuid::generate(),
        text: 'Reply text',
    ));
})->throws(CommentNotFoundException::class);

it('throws when comment belongs to different org', function () {
    $comment = Comment::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::Instagram,
        externalCommentId: 'ext-1',
        authorName: 'User',
        authorExternalId: null,
        authorProfileUrl: null,
        text: 'Text',
        sentiment: null,
        sentimentScore: null,
        isRead: false,
        isFromOwner: false,
        repliedAt: null,
        repliedBy: null,
        repliedByAutomation: false,
        replyText: null,
        replyExternalId: null,
        commentedAt: new DateTimeImmutable,
        capturedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $repo = Mockery::mock(CommentRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($comment);

    $factory = Mockery::mock(SocialEngagementFactoryInterface::class);

    $useCase = new ReplyCommentUseCase($repo, $factory);

    $useCase->execute(new ReplyCommentInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        commentId: (string) $comment->id,
        text: 'Reply text',
    ));
})->throws(CommentNotFoundException::class);
