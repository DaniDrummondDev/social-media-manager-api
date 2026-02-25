<?php

declare(strict_types=1);

namespace App\Application\Publishing\UseCases;

use App\Application\Publishing\DTOs\CalendarDayOutput;
use App\Application\Publishing\DTOs\CalendarOutput;
use App\Application\Publishing\DTOs\GetCalendarInput;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GetCalendarUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
    ) {}

    public function execute(GetCalendarInput $input): CalendarOutput
    {
        [$from, $to] = $this->resolvePeriod($input);

        $posts = $this->scheduledPostRepository->findByOrganizationId(
            organizationId: Uuid::fromString($input->organizationId),
            provider: $input->provider,
            campaignId: $input->campaignId,
            from: $from,
            to: $to,
        );

        /** @var array<string, array<array{id: string, scheduled_at: string, provider: string, status: string, content_title: ?string}>> $grouped */
        $grouped = [];

        foreach ($posts as $post) {
            $date = $post->scheduledAt->toDateTimeImmutable()->format('Y-m-d');

            $grouped[$date][] = [
                'id' => (string) $post->id,
                'scheduled_at' => $post->scheduledAt->toDateTimeImmutable()->format('c'),
                'provider' => '',
                'status' => $post->status->value,
                'content_title' => null,
            ];
        }

        ksort($grouped);

        $days = [];
        $totalPosts = 0;

        foreach ($grouped as $date => $dayPosts) {
            $days[] = new CalendarDayOutput(
                date: $date,
                posts: $dayPosts,
                count: count($dayPosts),
            );
            $totalPosts += count($dayPosts);
        }

        return new CalendarOutput(
            periodStart: $from->format('Y-m-d'),
            periodEnd: $to->format('Y-m-d'),
            days: $days,
            totalPosts: $totalPosts,
        );
    }

    /**
     * @return array{DateTimeImmutable, DateTimeImmutable}
     */
    private function resolvePeriod(GetCalendarInput $input): array
    {
        if ($input->startDate !== null && $input->endDate !== null) {
            return [
                new DateTimeImmutable($input->startDate.' 00:00:00'),
                new DateTimeImmutable($input->endDate.' 23:59:59'),
            ];
        }

        $month = $input->month ?? (int) date('m');
        $year = $input->year ?? (int) date('Y');

        $from = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $to = $from->modify('last day of this month')->modify('23:59:59');

        return [$from, $to];
    }
}
