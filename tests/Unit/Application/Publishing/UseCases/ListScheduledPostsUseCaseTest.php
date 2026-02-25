<?php

declare(strict_types=1);

use App\Application\Publishing\DTOs\ListScheduledPostsInput;
use App\Application\Publishing\UseCases\ListScheduledPostsUseCase;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);

    $this->useCase = new ListScheduledPostsUseCase(
        $this->scheduledPostRepository,
    );

    $this->orgId = (string) Uuid::generate();
});

it('lists posts with filters', function () {
    $post = ScheduledPost::reconstitute(
        id: Uuid::generate(),
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

    $this->scheduledPostRepository->shouldReceive('findByOrganizationId')->once()->andReturn([$post]);

    $output = $this->useCase->execute(new ListScheduledPostsInput(
        organizationId: $this->orgId,
        status: 'pending',
    ));

    expect($output->items)->toHaveCount(1)
        ->and($output->items[0]->status)->toBe('pending');
});

it('returns empty list', function () {
    $this->scheduledPostRepository->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $output = $this->useCase->execute(new ListScheduledPostsInput(
        organizationId: $this->orgId,
    ));

    expect($output->items)->toHaveCount(0);
});
