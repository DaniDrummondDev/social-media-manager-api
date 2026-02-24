<?php

declare(strict_types=1);

namespace App\Domain\Publishing\Entities;

use App\Domain\Publishing\Events\PostCancelled;
use App\Domain\Publishing\Events\PostDispatched;
use App\Domain\Publishing\Events\PostFailed;
use App\Domain\Publishing\Events\PostPublished;
use App\Domain\Publishing\Events\PostScheduled;
use App\Domain\Publishing\Exceptions\InvalidPublishingStatusTransitionException;
use App\Domain\Publishing\Exceptions\PublishingNotAllowedException;
use App\Domain\Publishing\ValueObjects\PublishError;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class ScheduledPost
{
    /** @var int[] Backoff in seconds per attempt (1min, 5min, 15min) */
    public const array BACKOFF_SECONDS = [60, 300, 900];

    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $contentId,
        public Uuid $socialAccountId,
        public Uuid $scheduledBy,
        public ScheduleTime $scheduledAt,
        public PublishingStatus $status,
        public ?DateTimeImmutable $publishedAt,
        public ?string $externalPostId,
        public ?string $externalPostUrl,
        public int $attempts,
        public int $maxAttempts,
        public ?DateTimeImmutable $lastAttemptedAt,
        public ?PublishError $lastError,
        public ?DateTimeImmutable $nextRetryAt,
        public ?DateTimeImmutable $dispatchedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $contentId,
        Uuid $socialAccountId,
        Uuid $scheduledBy,
        ScheduleTime $scheduledAt,
        int $maxAttempts = 3,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            contentId: $contentId,
            socialAccountId: $socialAccountId,
            scheduledBy: $scheduledBy,
            scheduledAt: $scheduledAt,
            status: PublishingStatus::Pending,
            publishedAt: null,
            externalPostId: null,
            externalPostUrl: null,
            attempts: 0,
            maxAttempts: $maxAttempts,
            lastAttemptedAt: null,
            lastError: null,
            nextRetryAt: null,
            dispatchedAt: null,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new PostScheduled(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $scheduledBy,
                    contentId: (string) $contentId,
                    socialAccountId: (string) $socialAccountId,
                    scheduledAt: $scheduledAt->toDateTimeImmutable()->format('c'),
                ),
            ],
        );
    }

    public static function createForImmediatePublish(
        Uuid $organizationId,
        Uuid $contentId,
        Uuid $socialAccountId,
        Uuid $scheduledBy,
        int $maxAttempts = 3,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;
        $scheduledAt = ScheduleTime::forImmediate();

        return new self(
            id: $id,
            organizationId: $organizationId,
            contentId: $contentId,
            socialAccountId: $socialAccountId,
            scheduledBy: $scheduledBy,
            scheduledAt: $scheduledAt,
            status: PublishingStatus::Dispatched,
            publishedAt: null,
            externalPostId: null,
            externalPostUrl: null,
            attempts: 0,
            maxAttempts: $maxAttempts,
            lastAttemptedAt: null,
            lastError: null,
            nextRetryAt: null,
            dispatchedAt: $now,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new PostScheduled(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $scheduledBy,
                    contentId: (string) $contentId,
                    socialAccountId: (string) $socialAccountId,
                    scheduledAt: $scheduledAt->toDateTimeImmutable()->format('c'),
                ),
                new PostDispatched(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $scheduledBy,
                    contentId: (string) $contentId,
                    socialAccountId: (string) $socialAccountId,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $contentId,
        Uuid $socialAccountId,
        Uuid $scheduledBy,
        ScheduleTime $scheduledAt,
        PublishingStatus $status,
        ?DateTimeImmutable $publishedAt,
        ?string $externalPostId,
        ?string $externalPostUrl,
        int $attempts,
        int $maxAttempts,
        ?DateTimeImmutable $lastAttemptedAt,
        ?PublishError $lastError,
        ?DateTimeImmutable $nextRetryAt,
        ?DateTimeImmutable $dispatchedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            contentId: $contentId,
            socialAccountId: $socialAccountId,
            scheduledBy: $scheduledBy,
            scheduledAt: $scheduledAt,
            status: $status,
            publishedAt: $publishedAt,
            externalPostId: $externalPostId,
            externalPostUrl: $externalPostUrl,
            attempts: $attempts,
            maxAttempts: $maxAttempts,
            lastAttemptedAt: $lastAttemptedAt,
            lastError: $lastError,
            nextRetryAt: $nextRetryAt,
            dispatchedAt: $dispatchedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function markAsDispatched(): self
    {
        $this->assertTransition(PublishingStatus::Dispatched);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            scheduledBy: $this->scheduledBy,
            scheduledAt: $this->scheduledAt,
            status: PublishingStatus::Dispatched,
            publishedAt: $this->publishedAt,
            externalPostId: $this->externalPostId,
            externalPostUrl: $this->externalPostUrl,
            attempts: $this->attempts,
            maxAttempts: $this->maxAttempts,
            lastAttemptedAt: $this->lastAttemptedAt,
            lastError: $this->lastError,
            nextRetryAt: $this->nextRetryAt,
            dispatchedAt: $now,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new PostDispatched(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->scheduledBy,
                    contentId: (string) $this->contentId,
                    socialAccountId: (string) $this->socialAccountId,
                ),
            ],
        );
    }

    public function markAsPublishing(): self
    {
        $this->assertTransition(PublishingStatus::Publishing);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            scheduledBy: $this->scheduledBy,
            scheduledAt: $this->scheduledAt,
            status: PublishingStatus::Publishing,
            publishedAt: $this->publishedAt,
            externalPostId: $this->externalPostId,
            externalPostUrl: $this->externalPostUrl,
            attempts: $this->attempts + 1,
            maxAttempts: $this->maxAttempts,
            lastAttemptedAt: $now,
            lastError: $this->lastError,
            nextRetryAt: null,
            dispatchedAt: $this->dispatchedAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: $this->domainEvents,
        );
    }

    public function markAsPublished(string $externalPostId, string $externalPostUrl): self
    {
        $this->assertTransition(PublishingStatus::Published);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            scheduledBy: $this->scheduledBy,
            scheduledAt: $this->scheduledAt,
            status: PublishingStatus::Published,
            publishedAt: $now,
            externalPostId: $externalPostId,
            externalPostUrl: $externalPostUrl,
            attempts: $this->attempts,
            maxAttempts: $this->maxAttempts,
            lastAttemptedAt: $this->lastAttemptedAt,
            lastError: $this->lastError,
            nextRetryAt: null,
            dispatchedAt: $this->dispatchedAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new PostPublished(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->scheduledBy,
                    contentId: (string) $this->contentId,
                    socialAccountId: (string) $this->socialAccountId,
                    externalPostId: $externalPostId,
                    publishedAt: $now->format('c'),
                ),
            ],
        );
    }

    public function markAsFailed(PublishError $error): self
    {
        $this->assertTransition(PublishingStatus::Failed);

        $now = new DateTimeImmutable;
        $nextRetryAt = null;

        if (! $error->isPermanent && $this->attempts < $this->maxAttempts) {
            $backoffIndex = min($this->attempts - 1, count(self::BACKOFF_SECONDS) - 1);
            $backoffSeconds = self::BACKOFF_SECONDS[max(0, $backoffIndex)];
            $nextRetryAt = $now->modify("+{$backoffSeconds} seconds");
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            scheduledBy: $this->scheduledBy,
            scheduledAt: $this->scheduledAt,
            status: PublishingStatus::Failed,
            publishedAt: $this->publishedAt,
            externalPostId: $this->externalPostId,
            externalPostUrl: $this->externalPostUrl,
            attempts: $this->attempts,
            maxAttempts: $this->maxAttempts,
            lastAttemptedAt: $this->lastAttemptedAt,
            lastError: $error,
            nextRetryAt: $nextRetryAt,
            dispatchedAt: $this->dispatchedAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new PostFailed(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->scheduledBy,
                    contentId: (string) $this->contentId,
                    socialAccountId: (string) $this->socialAccountId,
                    errorCode: $error->code,
                    isPermanent: $error->isPermanent,
                    attempts: $this->attempts,
                ),
            ],
        );
    }

    public function cancel(): self
    {
        if (! $this->canBeCancelled()) {
            throw new PublishingNotAllowedException(
                "Cannot cancel scheduled post in status '{$this->status->value}'.",
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            scheduledBy: $this->scheduledBy,
            scheduledAt: $this->scheduledAt,
            status: PublishingStatus::Cancelled,
            publishedAt: $this->publishedAt,
            externalPostId: $this->externalPostId,
            externalPostUrl: $this->externalPostUrl,
            attempts: $this->attempts,
            maxAttempts: $this->maxAttempts,
            lastAttemptedAt: $this->lastAttemptedAt,
            lastError: $this->lastError,
            nextRetryAt: null,
            dispatchedAt: $this->dispatchedAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new PostCancelled(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->scheduledBy,
                    contentId: (string) $this->contentId,
                    socialAccountId: (string) $this->socialAccountId,
                ),
            ],
        );
    }

    public function reschedule(ScheduleTime $newTime): self
    {
        if (! $this->canBeRescheduled()) {
            throw new PublishingNotAllowedException(
                "Cannot reschedule post in status '{$this->status->value}'.",
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            scheduledBy: $this->scheduledBy,
            scheduledAt: $newTime,
            status: $this->status,
            publishedAt: $this->publishedAt,
            externalPostId: $this->externalPostId,
            externalPostUrl: $this->externalPostUrl,
            attempts: $this->attempts,
            maxAttempts: $this->maxAttempts,
            lastAttemptedAt: $this->lastAttemptedAt,
            lastError: $this->lastError,
            nextRetryAt: $this->nextRetryAt,
            dispatchedAt: $this->dispatchedAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: $this->domainEvents,
        );
    }

    public function retryNow(): self
    {
        if (! $this->isRetryable()) {
            throw new PublishingNotAllowedException(
                'Cannot retry: post is not in a retryable state.',
            );
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            scheduledBy: $this->scheduledBy,
            scheduledAt: $this->scheduledAt,
            status: PublishingStatus::Publishing,
            publishedAt: $this->publishedAt,
            externalPostId: $this->externalPostId,
            externalPostUrl: $this->externalPostUrl,
            attempts: $this->attempts + 1,
            maxAttempts: $this->maxAttempts,
            lastAttemptedAt: $now,
            lastError: $this->lastError,
            nextRetryAt: null,
            dispatchedAt: $now,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new PostDispatched(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->scheduledBy,
                    contentId: (string) $this->contentId,
                    socialAccountId: (string) $this->socialAccountId,
                ),
            ],
        );
    }

    public function isRetryable(): bool
    {
        return $this->status === PublishingStatus::Failed
            && $this->attempts < $this->maxAttempts
            && ($this->lastError === null || ! $this->lastError->isPermanent);
    }

    public function isAlreadyPublished(): bool
    {
        return $this->status === PublishingStatus::Published;
    }

    public function isCancelled(): bool
    {
        return $this->status === PublishingStatus::Cancelled;
    }

    public function canBeCancelled(): bool
    {
        return $this->status === PublishingStatus::Pending;
    }

    public function canBeRescheduled(): bool
    {
        return $this->status === PublishingStatus::Pending;
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            contentId: $this->contentId,
            socialAccountId: $this->socialAccountId,
            scheduledBy: $this->scheduledBy,
            scheduledAt: $this->scheduledAt,
            status: $this->status,
            publishedAt: $this->publishedAt,
            externalPostId: $this->externalPostId,
            externalPostUrl: $this->externalPostUrl,
            attempts: $this->attempts,
            maxAttempts: $this->maxAttempts,
            lastAttemptedAt: $this->lastAttemptedAt,
            lastError: $this->lastError,
            nextRetryAt: $this->nextRetryAt,
            dispatchedAt: $this->dispatchedAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    private function assertTransition(PublishingStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidPublishingStatusTransitionException(
                $this->status->value,
                $target->value,
            );
        }
    }
}
