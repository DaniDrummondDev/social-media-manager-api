<?php

declare(strict_types=1);

namespace App\Domain\Organization\Entities;

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Organization\Events\MemberInvited;
use App\Domain\Organization\Exceptions\InviteAlreadyAcceptedException;
use App\Domain\Organization\Exceptions\InviteExpiredException;
use App\Domain\Organization\ValueObjects\OrganizationRole;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class OrganizationInvite
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Email $email,
        public string $token,
        public OrganizationRole $role,
        public Uuid $invitedBy,
        public ?DateTimeImmutable $acceptedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Email $email,
        OrganizationRole $role,
        Uuid $invitedBy,
        int $expirationDays = 7,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;
        $token = bin2hex(random_bytes(32));

        return new self(
            id: $id,
            organizationId: $organizationId,
            email: $email,
            token: $token,
            role: $role,
            invitedBy: $invitedBy,
            acceptedAt: null,
            expiresAt: $now->modify("+{$expirationDays} days"),
            createdAt: $now,
            domainEvents: [
                new MemberInvited(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $invitedBy,
                    inviteId: (string) $id,
                    email: (string) $email,
                    invitedBy: (string) $invitedBy,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Email $email,
        string $token,
        OrganizationRole $role,
        Uuid $invitedBy,
        ?DateTimeImmutable $acceptedAt,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            email: $email,
            token: $token,
            role: $role,
            invitedBy: $invitedBy,
            acceptedAt: $acceptedAt,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
        );
    }

    public function accept(): self
    {
        if ($this->acceptedAt !== null) {
            throw new InviteAlreadyAcceptedException;
        }

        if ($this->isExpired()) {
            throw new InviteExpiredException;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            email: $this->email,
            token: $this->token,
            role: $this->role,
            invitedBy: $this->invitedBy,
            acceptedAt: $now,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            domainEvents: [
                ...$this->domainEvents,
            ],
        );
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable > $this->expiresAt;
    }

    public function isPending(): bool
    {
        return $this->acceptedAt === null && ! $this->isExpired();
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            email: $this->email,
            token: $this->token,
            role: $this->role,
            invitedBy: $this->invitedBy,
            acceptedAt: $this->acceptedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
        );
    }
}
