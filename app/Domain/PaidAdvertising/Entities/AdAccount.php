<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Entities;

use App\Domain\PaidAdvertising\Events\AdAccountConnected;
use App\Domain\PaidAdvertising\Events\AdAccountDisconnected;
use App\Domain\PaidAdvertising\Exceptions\InvalidAdStatusTransitionException;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AdAccount
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $connectedBy,
        public AdProvider $provider,
        public string $providerAccountId,
        public string $providerAccountName,
        public AdAccountCredentials $credentials,
        public AdAccountStatus $status,
        public ?array $metadata,
        public DateTimeImmutable $connectedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $connectedBy,
        AdProvider $provider,
        string $providerAccountId,
        string $providerAccountName,
        AdAccountCredentials $credentials,
        ?array $metadata = null,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            connectedBy: $connectedBy,
            provider: $provider,
            providerAccountId: $providerAccountId,
            providerAccountName: $providerAccountName,
            credentials: $credentials,
            status: AdAccountStatus::Active,
            metadata: $metadata,
            connectedAt: $now,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new AdAccountConnected(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $connectedBy,
                    provider: $provider->value,
                    providerAccountId: $providerAccountId,
                ),
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $connectedBy,
        AdProvider $provider,
        string $providerAccountId,
        string $providerAccountName,
        AdAccountCredentials $credentials,
        AdAccountStatus $status,
        ?array $metadata,
        DateTimeImmutable $connectedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            connectedBy: $connectedBy,
            provider: $provider,
            providerAccountId: $providerAccountId,
            providerAccountName: $providerAccountName,
            credentials: $credentials,
            status: $status,
            metadata: $metadata,
            connectedAt: $connectedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function disconnect(string $userId): self
    {
        $this->assertTransition(AdAccountStatus::Disconnected);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerAccountId: $this->providerAccountId,
            providerAccountName: $this->providerAccountName,
            credentials: $this->credentials,
            status: AdAccountStatus::Disconnected,
            metadata: $this->metadata,
            connectedAt: $this->connectedAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: [
                ...$this->domainEvents,
                new AdAccountDisconnected(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    provider: $this->provider->value,
                ),
            ],
        );
    }

    public function refreshCredentials(AdAccountCredentials $newCredentials): self
    {
        $newStatus = $this->status === AdAccountStatus::TokenExpired
            ? AdAccountStatus::Active
            : $this->status;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerAccountId: $this->providerAccountId,
            providerAccountName: $this->providerAccountName,
            credentials: $newCredentials,
            status: $newStatus,
            metadata: $this->metadata,
            connectedAt: $this->connectedAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function markTokenExpired(): self
    {
        $this->assertTransition(AdAccountStatus::TokenExpired);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerAccountId: $this->providerAccountId,
            providerAccountName: $this->providerAccountName,
            credentials: $this->credentials,
            status: AdAccountStatus::TokenExpired,
            metadata: $this->metadata,
            connectedAt: $this->connectedAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function suspend(): self
    {
        $this->assertTransition(AdAccountStatus::Suspended);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerAccountId: $this->providerAccountId,
            providerAccountName: $this->providerAccountName,
            credentials: $this->credentials,
            status: AdAccountStatus::Suspended,
            metadata: $this->metadata,
            connectedAt: $this->connectedAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function reactivate(): self
    {
        $this->assertTransition(AdAccountStatus::Active);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerAccountId: $this->providerAccountId,
            providerAccountName: $this->providerAccountName,
            credentials: $this->credentials,
            status: AdAccountStatus::Active,
            metadata: $this->metadata,
            connectedAt: $this->connectedAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function isOperational(): bool
    {
        return $this->status->isOperational();
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerAccountId: $this->providerAccountId,
            providerAccountName: $this->providerAccountName,
            credentials: $this->credentials,
            status: $this->status,
            metadata: $this->metadata,
            connectedAt: $this->connectedAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    private function assertTransition(AdAccountStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidAdStatusTransitionException(
                $this->status->value,
                $target->value,
            );
        }
    }
}
