<?php

declare(strict_types=1);

use App\Application\Publishing\DTOs\GetScheduledPostInput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Publishing\UseCases\GetScheduledPostUseCase;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);

    $this->useCase = new GetScheduledPostUseCase(
        $this->scheduledPostRepository,
    );

    $this->orgId = (string) Uuid::generate();
    $this->postId = (string) Uuid::generate();
});

it('returns scheduled post successfully', function () {
    $post = ScheduledPost::reconstitute(
        id: Uuid::fromString($this->postId),
        organizationId: Uuid::fromString($this->orgId),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::fromDateTimeImmutable(new DateTimeImmutable('+1 day')),
        status: PublishingStatus::Pending,
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

    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn($post);

    $output = $this->useCase->execute(new GetScheduledPostInput(
        organizationId: $this->orgId,
        scheduledPostId: $this->postId,
    ));

    expect($output->id)->toBe($this->postId)
        ->and($output->status)->toBe('pending');
});

it('throws when post not found', function () {
    $this->scheduledPostRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new GetScheduledPostInput(
        organizationId: $this->orgId,
        scheduledPostId: $this->postId,
    ));
})->throws(ScheduledPostNotFoundException::class);
