<?php

declare(strict_types=1);

namespace App\Application\Publishing\UseCases;

use App\Application\Publishing\DTOs\ScheduledPostOutput;
use App\Application\Publishing\DTOs\SchedulePostInput;
use App\Application\Publishing\DTOs\SchedulePostOutput;
use App\Application\Publishing\Exceptions\ScheduledPostNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Exceptions\ContentNotFoundException;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\Exceptions\PublishingNotAllowedException;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use DateTimeImmutable;

final class SchedulePostUseCase
{
    public function __construct(
        private readonly ScheduledPostRepositoryInterface $scheduledPostRepository,
        private readonly ContentRepositoryInterface $contentRepository,
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(SchedulePostInput $input): SchedulePostOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $contentId = Uuid::fromString($input->contentId);
        $userId = Uuid::fromString($input->userId);

        $content = $this->contentRepository->findById($contentId);
        if ($content === null || (string) $content->organizationId !== $input->organizationId) {
            throw new ContentNotFoundException($input->contentId);
        }

        if ($content->status !== ContentStatus::Ready) {
            throw new PublishingNotAllowedException(
                "Content must be in 'ready' status to schedule. Current status: '{$content->status->value}'.",
            );
        }

        $scheduleTime = ScheduleTime::forFuture(new DateTimeImmutable($input->scheduledAt));

        /** @var DomainEvent[] $allEvents */
        $allEvents = [];
        $outputs = [];

        foreach ($input->socialAccountIds as $accountIdStr) {
            $accountId = Uuid::fromString($accountIdStr);

            $account = $this->socialAccountRepository->findById($accountId);
            if ($account === null || (string) $account->organizationId !== $input->organizationId) {
                throw new ScheduledPostNotFoundException($accountIdStr);
            }

            if (! $account->status->isActive()) {
                throw new PublishingNotAllowedException(
                    "Social account '{$account->username}' is not connected.",
                );
            }

            if ($this->scheduledPostRepository->existsByContentAndAccount($contentId, $accountId)) {
                throw new PublishingNotAllowedException(
                    "Content is already scheduled for account '{$account->username}'.",
                );
            }

            $post = ScheduledPost::create(
                organizationId: $organizationId,
                contentId: $contentId,
                socialAccountId: $accountId,
                scheduledBy: $userId,
                scheduledAt: $scheduleTime,
            );

            $this->scheduledPostRepository->create($post);
            $allEvents = [...$allEvents, ...$post->domainEvents];

            $outputs[] = ScheduledPostOutput::fromEntity(
                post: $post,
                provider: $account->provider->value,
                username: $account->username,
                contentTitle: $content->title,
            );
        }

        $updatedContent = $content->transitionTo(ContentStatus::Scheduled);
        $this->contentRepository->update($updatedContent);

        $this->eventDispatcher->dispatch(...$allEvents);

        return new SchedulePostOutput(
            contentId: $input->contentId,
            scheduledPosts: $outputs,
        );
    }
}
