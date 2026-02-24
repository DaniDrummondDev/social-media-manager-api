<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Repositories;

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Organization\Entities\OrganizationInvite;
use App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Organization\Models\OrganizationInviteModel;
use DateTimeImmutable;

final class EloquentOrganizationInviteRepository implements OrganizationInviteRepositoryInterface
{
    public function __construct(
        private readonly OrganizationInviteModel $model,
    ) {}

    public function create(OrganizationInvite $invite): void
    {
        $this->model->newQuery()->create($this->toArray($invite));
    }

    public function findByToken(string $token): ?OrganizationInvite
    {
        /** @var OrganizationInviteModel|null $record */
        $record = $this->model->newQuery()
            ->where('token', $token)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function findPendingByOrgAndEmail(Uuid $organizationId, Email $email): ?OrganizationInvite
    {
        /** @var OrganizationInviteModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('email', (string) $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    public function deleteExpired(): int
    {
        return $this->model->newQuery()
            ->where('expires_at', '<', now())
            ->whereNull('accepted_at')
            ->delete();
    }

    private function toDomain(OrganizationInviteModel $model): OrganizationInvite
    {
        $acceptedAt = $model->getAttribute('accepted_at')
            ? new DateTimeImmutable($model->getAttribute('accepted_at')->toDateTimeString())
            : null;

        return OrganizationInvite::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            email: Email::fromString($model->getAttribute('email')),
            token: $model->getAttribute('token'),
            role: OrganizationRole::from($model->getAttribute('role')),
            invitedBy: Uuid::fromString($model->getAttribute('invited_by')),
            acceptedAt: $acceptedAt,
            expiresAt: new DateTimeImmutable($model->getAttribute('expires_at')->toDateTimeString()),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(OrganizationInvite $invite): array
    {
        return [
            'id' => (string) $invite->id,
            'organization_id' => (string) $invite->organizationId,
            'email' => (string) $invite->email,
            'token' => $invite->token,
            'role' => $invite->role->value,
            'invited_by' => (string) $invite->invitedBy,
            'accepted_at' => $invite->acceptedAt?->format('Y-m-d H:i:s'),
            'expires_at' => $invite->expiresAt->format('Y-m-d H:i:s'),
        ];
    }
}
