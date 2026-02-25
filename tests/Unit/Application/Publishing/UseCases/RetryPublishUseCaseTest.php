<?php

declare(strict_types=1);

use App\Application\Publishing\DTOs\RetryPublishInput;
use App\Application\Publishing\UseCases\RetryPublishUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\Exceptions\PublishingNotAllowedException;
use App\Domain\Publishing\ValueObjects\PublishError;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new RetryPublishUseCase(
        $this->scheduledPostRepository,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->postId = (string) Uuid::generate();
});

it('retries successfully', function () {
    $post = ScheduledPost::reconstitute(
        id: Uuid::fromString($this->postId),
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::forImmediate(),
        status: PublishingStatus::Failed,
        publishedAt: null,
        externalPostId: null,
        externalPostUrl: null,
        attempts: 1,
        maxAttempts: 3,
        lastAttemptedAt: new DateTimeImmutable,
        lastError: new PublishError('PROVIDER_ERROR', 'Timeout', false),
        nextRetryAt: new DateTimeImmutable('+5 minutes'),
        dispatchedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);
    $this->scheduledPostRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $result = $this->useCase->execute(new RetryPublishInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
    ));

    expect($result['id'])->toBe($this->postId)
        ->and($result['status'])->toBe('publishing');
});

it('throws when post is not retryable', function () {
    $post = ScheduledPost::reconstitute(
        id: Uuid::fromString($this->postId),
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::forImmediate(),
        status: PublishingStatus::Failed,
        publishedAt: null,
        externalPostId: null,
        externalPostUrl: null,
        attempts: 3,
        maxAttempts: 3,
        lastAttemptedAt: new DateTimeImmutable,
        lastError: new PublishError('PERMANENT', 'Account suspended', true),
        nextRetryAt: null,
        dispatchedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);

    $this->useCase->execute(new RetryPublishInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
    ));
})->throws(PublishingNotAllowedException::class);
