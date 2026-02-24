<?php

declare(strict_types=1);

namespace App\Domain\Organization\Entities;

use App\Domain\Organization\Events\MemberAdded;
use App\Domain\Organization\Events\MemberRoleChanged;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class OrganizationMember
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $userId,
        public OrganizationRole $role,
        public ?Uuid $invitedBy,
        public DateTimeImmutable $joinedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $userId,
        OrganizationRole $role,
        ?Uuid $invitedBy = null,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            role: $role,
            invitedBy: $invitedBy,
            joinedAt: $now,
            domainEvents: [
                new MemberAdded(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $userId,
                    role: $role->value,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $userId,
        OrganizationRole $role,
        ?Uuid $invitedBy,
        DateTimeImmutable $joinedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            role: $role,
            invitedBy: $invitedBy,
            joinedAt: $joinedAt,
        );
    }

    public function changeRole(OrganizationRole $newRole): self
    {
        if ($this->role === $newRole) {
            return $this;
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            role: $newRole,
            invitedBy: $this->invitedBy,
            joinedAt: $this->joinedAt,
            domainEvents: [
                ...$this->domainEvents,
                new MemberRoleChanged(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->userId,
                    oldRole: $this->role->value,
                    newRole: $newRole->value,
                ),
            ],
        );
    }

    public function isOwner(): bool
    {
        return $this->role === OrganizationRole::Owner;
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            role: $this->role,
            invitedBy: $this->invitedBy,
            joinedAt: $this->joinedAt,
        );
    }
}
