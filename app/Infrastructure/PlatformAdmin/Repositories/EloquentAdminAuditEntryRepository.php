<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Repositories;

use App\Domain\PlatformAdmin\Entities\AdminAuditEntry;
use App\Domain\PlatformAdmin\Repositories\AdminAuditEntryRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PlatformAdmin\Models\AdminAuditEntryModel;
use DateTimeImmutable;

final class EloquentAdminAuditEntryRepository implements AdminAuditEntryRepositoryInterface
{
    public function __construct(
        private readonly AdminAuditEntryModel $model,
    ) {}

    public function create(AdminAuditEntry $entry): void
    {
        $this->model->newQuery()->create($this->toArray($entry));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<AdminAuditEntry>, next_cursor: ?string, has_more: bool}
     */
    public function findByFilters(array $filters, int $perPage, ?string $cursor): array
    {
        $query = $this->model->newQuery();

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (isset($filters['resource_id'])) {
            $query->where('resource_id', $filters['resource_id']);
        }

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, AdminAuditEntryModel> $records */
        $records = $query->orderByDesc('id')
            ->limit($perPage + 1)
            ->get();

        $hasMore = $records->count() > $perPage;

        if ($hasMore) {
            $records = $records->slice(0, $perPage);
        }

        $items = $records->map(fn (AdminAuditEntryModel $r) => $this->toDomain($r))->values()->all();

        $nextCursor = null;
        if ($hasMore && $records->isNotEmpty()) {
            $lastRecord = $records->last();
            $nextCursor = (string) $lastRecord->getAttribute('id');
        }

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    private function toDomain(AdminAuditEntryModel $model): AdminAuditEntry
    {
        $createdAt = $model->getAttribute('created_at');

        return AdminAuditEntry::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            adminId: Uuid::fromString($model->getAttribute('admin_id')),
            action: $model->getAttribute('action'),
            resourceType: $model->getAttribute('resource_type'),
            resourceId: $model->getAttribute('resource_id'),
            context: $model->getAttribute('context') ?? [],
            ipAddress: $model->getAttribute('ip_address'),
            userAgent: $model->getAttribute('user_agent'),
            createdAt: $createdAt instanceof \DateTimeInterface
                ? new DateTimeImmutable($createdAt->format('Y-m-d H:i:s'))
                : new DateTimeImmutable((string) $createdAt),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AdminAuditEntry $entry): array
    {
        return [
            'id' => (string) $entry->id,
            'admin_id' => (string) $entry->adminId,
            'action' => $entry->action,
            'resource_type' => $entry->resourceType,
            'resource_id' => $entry->resourceId,
            'context' => $entry->context,
            'ip_address' => $entry->ipAddress,
            'user_agent' => $entry->userAgent,
            'created_at' => $entry->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
