<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Repositories;

use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Organization\ValueObjects\OrganizationStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Organization\Models\OrganizationModel;
use DateTimeImmutable;

final class EloquentOrganizationRepository implements OrganizationRepositoryInterface
{
    public function __construct(
        private readonly OrganizationModel $model,
    ) {}

    public function create(Organization $organization): void
    {
        $this->model->newQuery()->create($this->toArray($organization));
    }

    public function update(Organization $organization): void
    {
        $this->model->newQuery()
            ->where('id', (string) $organization->id)
            ->update($this->toArray($organization));
    }

    public function findById(Uuid $id): ?Organization
    {
        /** @var OrganizationModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findBySlug(OrganizationSlug $slug): ?Organization
    {
        /** @var OrganizationModel|null $record */
        $record = $this->model->newQuery()
            ->where('slug', (string) $slug)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    /**
     * @return Organization[]
     */
    public function listByUserId(Uuid $userId): array
    {
        $records = $this->model->newQuery()
            ->whereIn('id', function ($query) use ($userId) {
                $query->select('organization_id')
                    ->from('organization_members')
                    ->where('user_id', (string) $userId);
            })
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, OrganizationModel> $records */
        return $records->map(fn (OrganizationModel $record) => $this->toDomain($record))->all();
    }

    private function toDomain(OrganizationModel $model): Organization
    {
        return Organization::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            name: $model->getAttribute('name'),
            slug: OrganizationSlug::fromString($model->getAttribute('slug')),
            timezone: $model->getAttribute('timezone'),
            status: OrganizationStatus::from($model->getAttribute('status')),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Organization $organization): array
    {
        return [
            'id' => (string) $organization->id,
            'name' => $organization->name,
            'slug' => (string) $organization->slug,
            'timezone' => $organization->timezone,
            'status' => $organization->status->value,
        ];
    }
}
