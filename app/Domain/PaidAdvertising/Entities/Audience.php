<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Entities;

use App\Domain\PaidAdvertising\Events\AudienceCreated;
use App\Domain\PaidAdvertising\Events\AudienceUpdated;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Audience
{
    /**
     * @param  array<string, string>|null  $providerAudienceIds  Map of provider => external audience ID
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $name,
        public TargetingSpec $targetingSpec,
        public ?array $providerAudienceIds,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        string $name,
        TargetingSpec $targetingSpec,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            targetingSpec: $targetingSpec,
            providerAudienceIds: null,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new AudienceCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    name: $name,
                ),
            ],
        );
    }

    /**
     * @param  array<string, string>|null  $providerAudienceIds
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $name,
        TargetingSpec $targetingSpec,
        ?array $providerAudienceIds,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            targetingSpec: $targetingSpec,
            providerAudienceIds: $providerAudienceIds,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function update(string $name, TargetingSpec $targetingSpec, string $userId): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $name,
            targetingSpec: $targetingSpec,
            providerAudienceIds: $this->providerAudienceIds,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                ...$this->domainEvents,
                new AudienceUpdated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    name: $name,
                ),
            ],
        );
    }

    public function setProviderAudienceId(string $provider, string $externalId): self
    {
        $ids = $this->providerAudienceIds ?? [];
        $ids[$provider] = $externalId;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            targetingSpec: $this->targetingSpec,
            providerAudienceIds: $ids,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function getProviderAudienceId(string $provider): ?string
    {
        return $this->providerAudienceIds[$provider] ?? null;
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            targetingSpec: $this->targetingSpec,
            providerAudienceIds: $this->providerAudienceIds,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
