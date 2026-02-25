<?php

declare(strict_types=1);

use App\Application\Publishing\DTOs\CancelScheduleInput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Publishing\UseCases\CancelScheduleUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\Exceptions\PublishingNotAllowedException;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);
    $this->contentRepository = Mockery::mock(ContentRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CancelScheduleUseCase(
        $this->scheduledPostRepository,
        $this->contentRepository,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->postId = (string) Uuid::generate();
    $this->contentId = Uuid::generate();
});

function makeScheduledPost(
    string $id,
    string $orgId,
    Uuid $contentId,
    PublishingStatus $status = PublishingStatus::Pending,
): ScheduledPost {
    return ScheduledPost::reconstitute(
        id: Uuid::fromString($id),
        organizationId: Uuid::fromString($orgId),
        contentId: $contentId,
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::fromDateTimeImmutable(new DateTimeImmutable('+1 day')),
        status: $status,
        publishedAt: null,
        externalPostId: null,
        externalPostUrl: null,
        attempts: 0,
        maxAttempts: 3,
        lastAttemptedAt: null,
        lastError: null,
        nextRetryAt: null,
        dispatchedAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('cancels successfully and reverts content to ready', function () {
    $post = makeScheduledPost($this->postId, $this->orgId, $this->contentId);

    $content = Content::reconstitute(
        id: $this->contentId,
        organizationId: Uuid::fromString($this->orgId),
        campaignId: Uuid::generate(),
        createdBy: Uuid::generate(),
        title: 'Test',
        body: 'Body',
        hashtags: [],
        status: ContentStatus::Scheduled,
        aiGenerationId: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
    );

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);
    $this->scheduledPostRepository->shouldReceive('update')->once();
    $this->contentRepository->shouldReceive('findById')->once()->andReturn($content);
    $this->scheduledPostRepository->shouldReceive('findByContentId')->once()->andReturn([$post]);
    $this->contentRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $result = $this->useCase->execute(new CancelScheduleInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
    ));

    expect($result['content_new_status'])->toBe('ready')
        ->and($result['content_id'])->toBe((string) $this->contentId);
});

it('throws when post not found', function () {
    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new CancelScheduleInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
    ));
})->throws(ScheduledPostNotFoundException::class);

it('throws when post belongs to different org', function () {
    $otherOrgId = (string) Uuid::generate();
    $post = makeScheduledPost($this->postId, $otherOrgId, $this->contentId);

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);

    $this->useCase->execute(new CancelScheduleInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
    ));
})->throws(ScheduledPostNotFoundException::class);

it('throws when status does not allow cancel', function () {
    $post = makeScheduledPost($this->postId, $this->orgId, $this->contentId, PublishingStatus::Published);

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);

    $this->useCase->execute(new CancelScheduleInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
    ));
})->throws(PublishingNotAllowedException::class);
