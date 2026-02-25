<?php

declare(strict_types=1);

namespace App\Application\Publishing\UseCases;

use App\Application\Publishing\DTOs\CancelScheduleInput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class CancelScheduleUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return array{message: string, content_id: string, content_new_status: string}
     */
    public function execute(CancelScheduleInput $input): array
    {
        $post = $this->scheduledPostRepository->findById(
            Uuid::fromString($input->scheduledPostId),
        );

        if ($post === null || (string) $post->organizationId !== $input->organizationId) {
            throw new ScheduledPostNotFoundException($input->scheduledPostId);
        }

        $cancelled = $post->cancel();
        $this->scheduledPostRepository->update($cancelled);

        $contentNewStatus = null;
        $content = $this->contentRepository->findById($post->contentId);

        if ($content !== null && $content->status === ContentStatus::Scheduled) {
            $hasActiveSchedules = $this->hasOtherActiveSchedules(
                $post->contentId,
                Uuid::fromString($input->scheduledPostId),
            );

            if (! $hasActiveSchedules) {
                $updatedContent = $content->transitionTo(ContentStatus::Ready);
                $this->contentRepository->update($updatedContent);
                $contentNewStatus = $updatedContent->status->value;
            } else {
                $contentNewStatus = $content->status->value;
            }
        }

        $this->eventDispatcher->dispatch(...$cancelled->domainEvents);

        return [
            'message' => 'Agendamento cancelado com sucesso.',
            'content_id' => (string) $post->contentId,
            'content_new_status' => $contentNewStatus ?? 'ready',
        ];
    }

    private function hasOtherActiveSchedules(Uuid $contentId, Uuid $excludePostId): bool
    {
        $posts = $this->scheduledPostRepository->findByContentId($contentId);

        foreach ($posts as $post) {
            if ((string) $post->id === (string) $excludePostId) {
                continue;
            }

            if (! $post->status->isTerminal()) {
                return true;
            }
        }

        return false;
    }
}
