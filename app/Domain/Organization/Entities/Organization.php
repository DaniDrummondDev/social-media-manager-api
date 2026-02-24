<?php

declare(strict_types=1);

namespace App\Domain\Organization\Entities;

use App\Domain\Organization\Events\OrganizationCreated;
use App\Domain\Organization\Events\OrganizationDeleted;
use App\Domain\Organization\Events\OrganizationUpdated;
use App\Domain\Organization\Exceptions\InvalidOrganizationNameException;
use App\Domain\Organization\Exceptions\OrganizationAlreadyDeletedException;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Organization\ValueObjects\OrganizationStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Organization
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public string $name,
        public OrganizationSlug $slug,
        public string $timezone,
        public OrganizationStatus $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        string $name,
        OrganizationSlug $slug,
        Uuid $ownerId,
        string $timezone = 'America/Sao_Paulo',
    ): self {
        self::validateName($name);

        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            timezone: $timezone,
            status: OrganizationStatus::Active,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new OrganizationCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $id,
                    userId: (string) $ownerId,
                    name: $name,
                    ownerId: (string) $ownerId,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        string $name,
        OrganizationSlug $slug,
        string $timezone,
        OrganizationStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            timezone: $timezone,
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function update(
        ?string $name = null,
        ?OrganizationSlug $slug = null,
        ?string $timezone = null,
        string $userId = '',
    ): self {
        $this->ensureNotDeleted();

        $changes = [];

        if ($name !== null && $name !== $this->name) {
            self::validateName($name);
            $changes['name'] = $name;
        }
        if ($slug !== null && ! $slug->equals($this->slug)) {
            $changes['slug'] = $slug->value;
        }
        if ($timezone !== null && $timezone !== $this->timezone) {
            $changes['timezone'] = $timezone;
        }

        if ($changes === []) {
            return $this;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $changes['name'] ?? $this->name,
            slug: $slug !== null && isset($changes['slug']) ? $slug : $this->slug,
            timezone: $changes['timezone'] ?? $this->timezone,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new OrganizationUpdated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->id,
                    userId: $userId,
                    changes: $changes,
                ),
            ],
        );
    }

    public function markAsDeleted(string $userId = ''): self
    {
        $this->ensureNotDeleted();

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            timezone: $this->timezone,
            status: OrganizationStatus::Deleted,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new OrganizationDeleted(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->id,
                    userId: $userId,
                ),
            ],
        );
    }

    public function suspend(string $userId = ''): self
    {
        $this->ensureNotDeleted();

        if (! $this->status->canTransitionTo(OrganizationStatus::Suspended)) {
            throw new OrganizationAlreadyDeletedException;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            timezone: $this->timezone,
            status: OrganizationStatus::Suspended,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new OrganizationUpdated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->id,
                    userId: $userId,
                    changes: ['status' => 'suspended'],
                ),
            ],
        );
    }

    public function reactivate(string $userId = ''): self
    {
        if (! $this->status->canTransitionTo(OrganizationStatus::Active)) {
            throw new OrganizationAlreadyDeletedException;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            timezone: $this->timezone,
            status: OrganizationStatus::Active,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new OrganizationUpdated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->id,
                    userId: $userId,
                    changes: ['status' => 'active'],
                ),
            ],
        );
    }

    public function isActive(): bool
    {
        return $this->status->canOperate();
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            timezone: $this->timezone,
            status: $this->status,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    private static function validateName(string $name): void
    {
        $trimmed = trim($name);

        if ($trimmed === '' || mb_strlen($trimmed) > 200) {
            throw new InvalidOrganizationNameException;
        }
    }

    private function ensureNotDeleted(): void
    {
        if ($this->status === OrganizationStatus::Deleted) {
            throw new OrganizationAlreadyDeletedException;
        }
    }
}
