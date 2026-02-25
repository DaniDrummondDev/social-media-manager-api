<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Repositories;

use App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface;
use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Publishing\ValueObjects\PublishError;
use App\Domain\Publishing\ValueObjects\PublishingStatus;
use App\Domain\Publishing\ValueObjects\ScheduleTime;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Publishing\Models\ScheduledPostModel;
use DateTimeImmutable;

final class EloquentScheduledPostRepository implements ScheduledPostRepositoryInterface
{
    public function __construct(
        private readonly ScheduledPostModel $model,
    ) {}

    public function create(ScheduledPost $post): void
    {
        $this->model->newQuery()->create($this->toArray($post));
    }

    public function update(ScheduledPost $post): void
    {
        $this->model->newQuery()
            ->where('id', (string) $post->id)
            ->update($this->toArray($post));
    }

    public function findById(Uuid $id): ?ScheduledPost
    {
        /** @var ScheduledPostModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return ScheduledPost[]
     */
    public function findByOrganizationId(
        Uuid $organizationId,
        ?string $status = null,
        ?string $provider = null,
        ?string $campaignId = null,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null,
    ): array {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->orderByDesc('scheduled_at');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($from !== null) {
            $query->where('scheduled_at', '>=', $from->format('Y-m-d H:i:s'));
        }

        if ($to !== null) {
            $query->where('scheduled_at', '<=', $to->format('Y-m-d H:i:s'));
        }

        $records = $query->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ScheduledPostModel> $records */
        return $records->map(fn (ScheduledPostModel $record) => $this->toDomain($record))->all();
    }

    /**
     * @return ScheduledPost[]
     */
    public function findDuePosts(DateTimeImmutable $now): array
    {
        $records = $this->model->newQuery()
            ->where('status', PublishingStatus::Pending->value)
            ->where('scheduled_at', '<=', $now->format('Y-m-d H:i:s'))
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ScheduledPostModel> $records */
        return $records->map(fn (ScheduledPostModel $record) => $this->toDomain($record))->all();
    }

    /**
     * @return ScheduledPost[]
     */
    public function findRetryable(DateTimeImmutable $now): array
    {
        $records = $this->model->newQuery()
            ->where('status', PublishingStatus::Failed->value)
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', $now->format('Y-m-d H:i:s'))
            ->whereColumn('attempts', '<', 'max_attempts')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ScheduledPostModel> $records */
        return $records->map(fn (ScheduledPostModel $record) => $this->toDomain($record))->all();
    }

    /**
     * @return ScheduledPost[]
     */
    public function findByContentId(Uuid $contentId): array
    {
        $records = $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ScheduledPostModel> $records */
        return $records->map(fn (ScheduledPostModel $record) => $this->toDomain($record))->all();
    }

    public function existsByContentAndAccount(Uuid $contentId, Uuid $socialAccountId): bool
    {
        return $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->where('social_account_id', (string) $socialAccountId)
            ->whereNotIn('status', [
                PublishingStatus::Cancelled->value,
                PublishingStatus::Failed->value,
            ])
            ->exists();
    }

    private function toDomain(ScheduledPostModel $model): ScheduledPost
    {
        $lastError = null;
        $errorData = $model->getAttribute('last_error');

        if ($errorData !== null) {
            $lastError = new PublishError(
                code: $errorData['code'] ?? 'UNKNOWN',
                message: $errorData['message'] ?? '',
                isPermanent: $errorData['is_permanent'] ?? false,
            );
        }

        return ScheduledPost::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            contentId: Uuid::fromString($model->getAttribute('content_id')),
            socialAccountId: Uuid::fromString($model->getAttribute('social_account_id')),
            scheduledBy: Uuid::fromString($model->getAttribute('scheduled_by')),
            scheduledAt: ScheduleTime::fromDateTimeImmutable(
                new DateTimeImmutable($model->getAttribute('scheduled_at')->toDateTimeString()),
            ),
            status: PublishingStatus::from($model->getAttribute('status')),
            publishedAt: $model->getAttribute('published_at')
                ? new DateTimeImmutable($model->getAttribute('published_at')->toDateTimeString())
                : null,
            externalPostId: $model->getAttribute('external_post_id'),
            externalPostUrl: $model->getAttribute('external_post_url'),
            attempts: (int) $model->getAttribute('attempts'),
            maxAttempts: (int) $model->getAttribute('max_attempts'),
            lastAttemptedAt: $model->getAttribute('last_attempted_at')
                ? new DateTimeImmutable($model->getAttribute('last_attempted_at')->toDateTimeString())
                : null,
            lastError: $lastError,
            nextRetryAt: $model->getAttribute('next_retry_at')
                ? new DateTimeImmutable($model->getAttribute('next_retry_at')->toDateTimeString())
                : null,
            dispatchedAt: $model->getAttribute('dispatched_at')
                ? new DateTimeImmutable($model->getAttribute('dispatched_at')->toDateTimeString())
                : null,
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ScheduledPost $post): array
    {
        return [
            'id' => (string) $post->id,
            'organization_id' => (string) $post->organizationId,
            'content_id' => (string) $post->contentId,
            'social_account_id' => (string) $post->socialAccountId,
            'scheduled_by' => (string) $post->scheduledBy,
            'scheduled_at' => $post->scheduledAt->toDateTimeImmutable()->format('Y-m-d H:i:s'),
            'status' => $post->status->value,
            'published_at' => $post->publishedAt?->format('Y-m-d H:i:s'),
            'external_post_id' => $post->externalPostId,
            'external_post_url' => $post->externalPostUrl,
            'attempts' => $post->attempts,
            'max_attempts' => $post->maxAttempts,
            'last_attempted_at' => $post->lastAttemptedAt?->format('Y-m-d H:i:s'),
            'last_error' => $post->lastError?->toArray(),
            'next_retry_at' => $post->nextRetryAt?->format('Y-m-d H:i:s'),
            'dispatched_at' => $post->dispatchedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
