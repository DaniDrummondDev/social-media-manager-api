<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Entities\Comment;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Engagement\ValueObjects\Sentiment;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Engagement\Models\CommentModel;
use DateTimeImmutable;

final class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        private readonly CommentModel $model,
    ) {}

    public function create(Comment $comment): void
    {
        $this->model->newQuery()->create($this->toArray($comment));
    }

    public function update(Comment $comment): void
    {
        $this->model->newQuery()
            ->where('id', (string) $comment->id)
            ->update($this->toArray($comment));
    }

    public function findById(Uuid $id): ?Comment
    {
        /** @var CommentModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<Comment>
     */
    public function findByOrganizationId(Uuid $organizationId, array $filters = [], ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if (isset($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (isset($filters['sentiment'])) {
            $query->where('sentiment', $filters['sentiment']);
        }

        if (isset($filters['is_read'])) {
            $query->where('is_read', (bool) $filters['is_read']);
        }

        if (isset($filters['is_replied'])) {
            if ($filters['is_replied']) {
                $query->whereNotNull('replied_at');
            } else {
                $query->whereNull('replied_at');
            }
        }

        if (isset($filters['content_id'])) {
            $query->where('content_id', $filters['content_id']);
        }

        if (isset($filters['external_comment_id']) && isset($filters['social_account_id'])) {
            $query->where('external_comment_id', $filters['external_comment_id'])
                ->where('social_account_id', $filters['social_account_id']);
        }

        if (isset($filters['search'])) {
            $query->where('text', 'LIKE', '%'.$filters['search'].'%');
        }

        if (isset($filters['from'])) {
            $query->where('commented_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('commented_at', '<=', $filters['to']);
        }

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, CommentModel> $records */
        $records = $query->orderByDesc('id')->limit($limit)->get();

        return $records->map(fn (CommentModel $r) => $this->toDomain($r))->all();
    }

    public function markAsRead(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->update(['is_read' => true, 'updated_at' => now()]);
    }

    /**
     * @param  array<string>  $ids
     */
    public function markManyAsRead(Uuid $organizationId, array $ids): void
    {
        $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereIn('id', $ids)
            ->update(['is_read' => true, 'updated_at' => now()]);
    }

    public function countUnread(Uuid $organizationId): int
    {
        return (int) $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Comment $comment): array
    {
        return [
            'id' => (string) $comment->id,
            'organization_id' => (string) $comment->organizationId,
            'content_id' => (string) $comment->contentId,
            'social_account_id' => (string) $comment->socialAccountId,
            'provider' => $comment->provider->value,
            'external_comment_id' => $comment->externalCommentId,
            'author_name' => $comment->authorName,
            'author_external_id' => $comment->authorExternalId,
            'author_profile_url' => $comment->authorProfileUrl,
            'text' => $comment->text,
            'sentiment' => $comment->sentiment?->value,
            'sentiment_score' => $comment->sentimentScore,
            'is_read' => $comment->isRead,
            'is_from_owner' => $comment->isFromOwner,
            'replied_at' => $comment->repliedAt?->format('Y-m-d H:i:s'),
            'replied_by' => $comment->repliedBy !== null ? (string) $comment->repliedBy : null,
            'replied_by_automation' => $comment->repliedByAutomation,
            'reply_text' => $comment->replyText,
            'reply_external_id' => $comment->replyExternalId,
            'commented_at' => $comment->commentedAt->format('Y-m-d H:i:s'),
            'captured_at' => $comment->capturedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(CommentModel $model): Comment
    {
        $repliedAt = $model->getAttribute('replied_at');
        $commentedAt = $model->getAttribute('commented_at');
        $capturedAt = $model->getAttribute('captured_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');
        $sentiment = $model->getAttribute('sentiment');
        $repliedBy = $model->getAttribute('replied_by');

        return Comment::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            contentId: Uuid::fromString($model->getAttribute('content_id')),
            socialAccountId: Uuid::fromString($model->getAttribute('social_account_id')),
            provider: SocialProvider::from($model->getAttribute('provider')),
            externalCommentId: $model->getAttribute('external_comment_id'),
            authorName: $model->getAttribute('author_name'),
            authorExternalId: $model->getAttribute('author_external_id'),
            authorProfileUrl: $model->getAttribute('author_profile_url'),
            text: $model->getAttribute('text'),
            sentiment: $sentiment !== null ? Sentiment::from($sentiment) : null,
            sentimentScore: $model->getAttribute('sentiment_score'),
            isRead: (bool) $model->getAttribute('is_read'),
            isFromOwner: (bool) $model->getAttribute('is_from_owner'),
            repliedAt: $repliedAt ? new DateTimeImmutable($repliedAt->format('Y-m-d H:i:s')) : null,
            repliedBy: $repliedBy !== null ? Uuid::fromString($repliedBy) : null,
            repliedByAutomation: (bool) $model->getAttribute('replied_by_automation'),
            replyText: $model->getAttribute('reply_text'),
            replyExternalId: $model->getAttribute('reply_external_id'),
            commentedAt: new DateTimeImmutable($commentedAt->format('Y-m-d H:i:s')),
            capturedAt: new DateTimeImmutable($capturedAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
