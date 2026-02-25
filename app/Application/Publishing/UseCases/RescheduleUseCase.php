<?php

declare(strict_types=1);

namespace App\Application\Publishing\UseCases;

use App\Application\Publishing\DTOs\RescheduleInput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class RescheduleUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return array{id: string, scheduled_at: string, status: string, message: string}
     */
    public function execute(RescheduleInput $input): array
    {
        $post = $this->scheduledPostRepository->findById(
            Uuid::fromString($input->scheduledPostId),
        );

        if ($post === null || (string) $post->organizationId !== $input->organizationId) {
            throw new ScheduledPostNotFoundException($input->scheduledPostId);
        }

        $newTime = ScheduleTime::forFuture(new DateTimeImmutable($input->scheduledAt));
        $rescheduled = $post->reschedule($newTime);

        $this->scheduledPostRepository->update($rescheduled);
        $this->eventDispatcher->dispatch(...$rescheduled->domainEvents);

        return [
            'id' => (string) $rescheduled->id,
            'scheduled_at' => $rescheduled->scheduledAt->toDateTimeImmutable()->format('c'),
            'status' => $rescheduled->status->value,
            'message' => 'Reagendado com sucesso.',
        ];
    }
}
