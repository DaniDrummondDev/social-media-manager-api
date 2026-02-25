<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Repositories;

use App\Domain\PlatformAdmin\Entities\PlatformAdmin;
use App\Domain\PlatformAdmin\Repositories\PlatformAdminRepositoryInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PlatformAdmin\Models\PlatformAdminModel;
use DateTimeImmutable;

final class EloquentPlatformAdminRepository implements PlatformAdminRepositoryInterface
{
    public function __construct(
        private readonly PlatformAdminModel $model,
    ) {}

    public function findById(Uuid $id): ?PlatformAdmin
    {
        /** @var PlatformAdminModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByUserId(Uuid $userId): ?PlatformAdmin
    {
        /** @var PlatformAdminModel|null $record */
        $record = $this->model->newQuery()
            ->where('user_id', (string) $userId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<PlatformAdmin>
     */
    public function findAll(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, PlatformAdminModel> $records */
        $records = $this->model->newQuery()->get();

        return $records->map(fn (PlatformAdminModel $r) => $this->toDomain($r))->all();
    }

    public function create(PlatformAdmin $admin): void
    {
        $this->model->newQuery()->create($this->toArray($admin));
    }

    public function update(PlatformAdmin $admin): void
    {
        $this->model->newQuery()
            ->where('id', (string) $admin->id)
            ->update($this->toArray($admin));
    }

    public function countActiveSuperAdmins(): int
    {
        return (int) $this->model->newQuery()
            ->where('is_active', true)
            ->where('role', PlatformRole::SuperAdmin->value)
            ->count();
    }

    private function toDomain(PlatformAdminModel $model): PlatformAdmin
    {
        $lastLoginAt = $model->getAttribute('last_login_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return PlatformAdmin::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            userId: Uuid::fromString($model->getAttribute('user_id')),
            role: PlatformRole::from($model->getAttribute('role')),
            permissions: $model->getAttribute('permissions') ?? [],
            isActive: (bool) $model->getAttribute('is_active'),
            lastLoginAt: $lastLoginAt ? new DateTimeImmutable($lastLoginAt->toDateTimeString()) : null,
            createdAt: new DateTimeImmutable($createdAt->toDateTimeString()),
            updatedAt: new DateTimeImmutable($updatedAt->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(PlatformAdmin $admin): array
    {
        return [
            'id' => (string) $admin->id,
            'user_id' => (string) $admin->userId,
            'role' => $admin->role->value,
            'permissions' => $admin->permissions,
            'is_active' => $admin->isActive,
            'last_login_at' => $admin->lastLoginAt?->format('Y-m-d H:i:s'),
        ];
    }
}
