<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\Entities\Comment;
use App\Domain\Shared\ValueObjects\Uuid;

interface CommentRepositoryInterface
{
    public function create(Comment $comment): void;

    public function update(Comment $comment): void;

    public function findById(Uuid $id): ?Comment;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<Comment>
     */
    public function findByOrganizationId(Uuid $organizationId, array $filters = [], ?string $cursor = null, int $limit = 20): array;

    public function markAsRead(Uuid $id): void;

    /**
     * @param  array<string>  $ids
     */
    public function markManyAsRead(Uuid $organizationId, array $ids): void;

    public function countUnread(Uuid $organizationId): int;
}
