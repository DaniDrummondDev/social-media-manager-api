<?php

declare(strict_types=1);

use App\Application\Publishing\DTOs\RescheduleInput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Publishing\UseCases\RescheduleUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\Exceptions\PublishingNotAllowedException;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new RescheduleUseCase(
        $this->scheduledPostRepository,
        $this->eventDispatcher,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
    $this->postId = (string) Uuid::generate();
    $this->newScheduledAt = (new DateTimeImmutable('+2 days'))->format('c');
});

function makePostForReschedule(
    string $id,
    string $orgId,
    PublishingStatus $status = PublishingStatus::Pending,
): ScheduledPost {
    return ScheduledPost::reconstitute(
        id: Uuid::fromString($id),
        organizationId: Uuid::fromString($orgId),
        contentId: Uuid::generate(),
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

it('reschedules successfully', function () {
    $post = makePostForReschedule($this->postId, $this->orgId);

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);
    $this->scheduledPostRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $result = $this->useCase->execute(new RescheduleInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
        scheduledAt: $this->newScheduledAt,
    ));

    expect($result['id'])->toBe($this->postId)
        ->and($result['status'])->toBe('pending')
        ->and($result['message'])->toBeString();
});

it('throws when post not found', function () {
    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new RescheduleInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
        scheduledAt: $this->newScheduledAt,
    ));
})->throws(ScheduledPostNotFoundException::class);

it('throws when status does not allow reschedule', function () {
    $post = makePostForReschedule($this->postId, $this->orgId, PublishingStatus::Published);

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);

    $this->useCase->execute(new RescheduleInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        scheduledPostId: $this->postId,
        scheduledAt: $this->newScheduledAt,
    ));
})->throws(PublishingNotAllowedException::class);
