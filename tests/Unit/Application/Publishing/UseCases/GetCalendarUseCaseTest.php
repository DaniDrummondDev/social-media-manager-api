<?php

declare(strict_types=1);

use App\Application\Publishing\DTOs\GetCalendarInput;
use App\Application\Publishing\UseCases\GetCalendarUseCase;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->scheduledPostRepository = Mockery::mock(ScheduledPostRepositoryInterface::class);

    $this->useCase = new GetCalendarUseCase(
        $this->scheduledPostRepository,
    );

    $this->orgId = (string) Uuid::generate();
});

function makeCalendarPost(string $scheduledAt): ScheduledPost
{
    return ScheduledPost::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        scheduledBy: Uuid::generate(),
        scheduledAt: ScheduleTime::fromDateTimeImmutable(new DateTimeImmutable($scheduledAt)),
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
}

it('returns calendar grouped by day using month/year', function () {
    $post1 = makeCalendarPost('2026-03-10 10:00:00');
    $post2 = makeCalendarPost('2026-03-10 14:00:00');
    $post3 = makeCalendarPost('2026-03-15 09:00:00');

    $this->scheduledPostRepository->shouldReceive('findByOrganizationId')->once()->andReturn([$post1, $post2, $post3]);

    $output = $this->useCase->execute(new GetCalendarInput(
        organizationId: $this->orgId,
        month: 3,
        year: 2026,
    ));

    expect($output->periodStart)->toBe('2026-03-01')
        ->and($output->periodEnd)->toBe('2026-03-31')
        ->and($output->days)->toHaveCount(2)
        ->and($output->days[0]->date)->toBe('2026-03-10')
        ->and($output->days[0]->count)->toBe(2)
        ->and($output->days[1]->date)->toBe('2026-03-15')
        ->and($output->days[1]->count)->toBe(1)
        ->and($output->totalPosts)->toBe(3);
});

it('returns calendar using start_date/end_date', function () {
    $post = makeCalendarPost('2026-04-05 12:00:00');

    $this->scheduledPostRepository->shouldReceive('findByOrganizationId')->once()->andReturn([$post]);

    $output = $this->useCase->execute(new GetCalendarInput(
        organizationId: $this->orgId,
        startDate: '2026-04-01',
        endDate: '2026-04-30',
    ));

    expect($output->periodStart)->toBe('2026-04-01')
        ->and($output->periodEnd)->toBe('2026-04-30')
        ->and($output->days)->toHaveCount(1)
        ->and($output->totalPosts)->toBe(1);
});

it('returns empty calendar', function () {
    $this->scheduledPostRepository->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $output = $this->useCase->execute(new GetCalendarInput(
        organizationId: $this->orgId,
        month: 1,
        year: 2026,
    ));

    expect($output->days)->toHaveCount(0)
        ->and($output->totalPosts)->toBe(0);
});
