<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Repositories;

use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Organization\Models\OrganizationMemberModel;
use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentOrganizationMemberRepository implements OrganizationMemberRepositoryInterface
{
    public function __construct(
        private readonly OrganizationMemberModel $model,
    ) {}

    public function create(OrganizationMember $member): void
    {
        $this->model->newQuery()->create($this->toArray($member));
    }

    public function createIfNotExists(OrganizationMember $member): bool
    {
        try {
            $this->create($member);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    public function update(OrganizationMember $member): void
    {
        $this->model->newQuery()
            ->where('id', (string) $member->id)
            ->update($this->toArray($member));
    }

    public function findByOrgAndUser(Uuid $organizationId, Uuid $userId): ?OrganizationMember
    {
        /** @var OrganizationMemberModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('user_id', (string) $userId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return OrganizationMember[]
     */
    public function listByOrganization(Uuid $organizationId): array
    {
        return $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->get()
            ->map(fn (OrganizationMemberModel $record) => $this->toDomain($record))
            ->all();
    }

    public function delete(Uuid $organizationId, Uuid $userId): void
    {
        $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('user_id', (string) $userId)
            ->delete();
    }

    public function countByOrganization(Uuid $organizationId): int
    {
        return $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->count();
    }

    private function toDomain(OrganizationMemberModel $model): OrganizationMember
    {
        $invitedBy = $model->getAttribute('invited_by')
            ? Uuid::fromString($model->getAttribute('invited_by'))
            : null;

        return OrganizationMember::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            userId: Uuid::fromString($model->getAttribute('user_id')),
            role: OrganizationRole::from($model->getAttribute('role')),
            invitedBy: $invitedBy,
            joinedAt: new DateTimeImmutable($model->getAttribute('joined_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(OrganizationMember $member): array
    {
        return [
            'id' => (string) $member->id,
            'organization_id' => (string) $member->organizationId,
            'user_id' => (string) $member->userId,
            'role' => $member->role->value,
            'invited_by' => $member->invitedBy ? (string) $member->invitedBy : null,
            'joined_at' => $member->joinedAt->format('Y-m-d H:i:s'),
        ];
    }
}
