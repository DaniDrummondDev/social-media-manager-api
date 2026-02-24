<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Entities;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Events\SocialAccountConnected;
use App\Domain\SocialAccount\Events\SocialAccountDisconnected;
use App\Domain\SocialAccount\Events\TokenExpired;
use App\Domain\SocialAccount\Events\TokenRefreshed;
use App\Domain\SocialAccount\Exceptions\SocialAccountNotConnectedException;
use App\Domain\SocialAccount\ValueObjects\ConnectionStatus;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use DateTimeImmutable;

final readonly class SocialAccount
{
    /**
     * @param  ?array<string, mixed>  $metadata
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $connectedBy,
        public SocialProvider $provider,
        public string $providerUserId,
        public string $username,
        public ?string $displayName,
        public ?string $profilePictureUrl,
        public OAuthCredentials $credentials,
        public ConnectionStatus $status,
        public ?DateTimeImmutable $lastSyncedAt,
        public DateTimeImmutable $connectedAt,
        public ?DateTimeImmutable $disconnectedAt,
        public ?array $metadata,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  ?array<string, mixed>  $metadata
     */
    public static function create(
        Uuid $organizationId,
        Uuid $connectedBy,
        SocialProvider $provider,
        string $providerUserId,
        string $username,
        OAuthCredentials $credentials,
        ?string $displayName = null,
        ?string $profilePictureUrl = null,
        ?array $metadata = null,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            connectedBy: $connectedBy,
            provider: $provider,
            providerUserId: $providerUserId,
            username: $username,
            displayName: $displayName,
            profilePictureUrl: $profilePictureUrl,
            credentials: $credentials,
            status: ConnectionStatus::Connected,
            lastSyncedAt: null,
            connectedAt: $now,
            disconnectedAt: null,
            metadata: $metadata,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
            domainEvents: [
                new SocialAccountConnected(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $connectedBy,
                    provider: $provider->value,
                    username: $username,
                ),
            ],
        );
    }

    /**
     * @param  ?array<string, mixed>  $metadata
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $connectedBy,
        SocialProvider $provider,
        string $providerUserId,
        string $username,
        ?string $displayName,
        ?string $profilePictureUrl,
        OAuthCredentials $credentials,
        ConnectionStatus $status,
        ?DateTimeImmutable $lastSyncedAt,
        DateTimeImmutable $connectedAt,
        ?DateTimeImmutable $disconnectedAt,
        ?array $metadata,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            connectedBy: $connectedBy,
            provider: $provider,
            providerUserId: $providerUserId,
            username: $username,
            displayName: $displayName,
            profilePictureUrl: $profilePictureUrl,
            credentials: $credentials,
            status: $status,
            lastSyncedAt: $lastSyncedAt,
            connectedAt: $connectedAt,
            disconnectedAt: $disconnectedAt,
            metadata: $metadata,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );
    }

    public function disconnect(string $userId): self
    {
        $this->ensureNotDisconnected();

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $this->username,
            displayName: $this->displayName,
            profilePictureUrl: $this->profilePictureUrl,
            credentials: $this->credentials,
            status: ConnectionStatus::Disconnected,
            lastSyncedAt: $this->lastSyncedAt,
            connectedAt: $this->connectedAt,
            disconnectedAt: $now,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            domainEvents: [
                ...$this->domainEvents,
                new SocialAccountDisconnected(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    provider: $this->provider->value,
                    username: $this->username,
                ),
            ],
        );
    }

    public function refreshToken(OAuthCredentials $newCredentials): self
    {
        $this->ensureNotDisconnected();

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $this->username,
            displayName: $this->displayName,
            profilePictureUrl: $this->profilePictureUrl,
            credentials: $newCredentials,
            status: ConnectionStatus::Connected,
            lastSyncedAt: $this->lastSyncedAt,
            connectedAt: $this->connectedAt,
            disconnectedAt: $this->disconnectedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            domainEvents: [
                ...$this->domainEvents,
                new TokenRefreshed(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->connectedBy,
                    provider: $this->provider->value,
                ),
            ],
        );
    }

    public function markTokenExpired(): self
    {
        $this->ensureNotDisconnected();

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $this->username,
            displayName: $this->displayName,
            profilePictureUrl: $this->profilePictureUrl,
            credentials: $this->credentials,
            status: ConnectionStatus::TokenExpired,
            lastSyncedAt: $this->lastSyncedAt,
            connectedAt: $this->connectedAt,
            disconnectedAt: $this->disconnectedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            domainEvents: [
                ...$this->domainEvents,
                new TokenExpired(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->connectedBy,
                    provider: $this->provider->value,
                ),
            ],
        );
    }

    public function markRequiresReconnection(): self
    {
        $this->ensureNotDisconnected();

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $this->username,
            displayName: $this->displayName,
            profilePictureUrl: $this->profilePictureUrl,
            credentials: $this->credentials,
            status: ConnectionStatus::RequiresReconnection,
            lastSyncedAt: $this->lastSyncedAt,
            connectedAt: $this->connectedAt,
            disconnectedAt: $this->disconnectedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function reconnect(OAuthCredentials $newCredentials, string $userId): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $this->username,
            displayName: $this->displayName,
            profilePictureUrl: $this->profilePictureUrl,
            credentials: $newCredentials,
            status: ConnectionStatus::Connected,
            lastSyncedAt: $this->lastSyncedAt,
            connectedAt: $now,
            disconnectedAt: null,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            domainEvents: [
                ...$this->domainEvents,
                new SocialAccountConnected(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    provider: $this->provider->value,
                    username: $this->username,
                ),
            ],
        );
    }

    public function updateProfile(
        ?string $username = null,
        ?string $displayName = null,
        ?string $profilePictureUrl = null,
    ): self {
        if ($username === null && $displayName === null && $profilePictureUrl === null) {
            return $this;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $username ?? $this->username,
            displayName: $displayName ?? $this->displayName,
            profilePictureUrl: $profilePictureUrl ?? $this->profilePictureUrl,
            credentials: $this->credentials,
            status: $this->status,
            lastSyncedAt: $this->lastSyncedAt,
            connectedAt: $this->connectedAt,
            disconnectedAt: $this->disconnectedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function recordSync(): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $this->username,
            displayName: $this->displayName,
            profilePictureUrl: $this->profilePictureUrl,
            credentials: $this->credentials,
            status: $this->status,
            lastSyncedAt: $now,
            connectedAt: $this->connectedAt,
            disconnectedAt: $this->disconnectedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function isActive(): bool
    {
        return $this->status->isActive() && $this->deletedAt === null;
    }

    public function isTokenExpired(): bool
    {
        return $this->status === ConnectionStatus::TokenExpired;
    }

    public function softDelete(): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $this->username,
            displayName: $this->displayName,
            profilePictureUrl: $this->profilePictureUrl,
            credentials: $this->credentials,
            status: ConnectionStatus::Disconnected,
            lastSyncedAt: $this->lastSyncedAt,
            connectedAt: $this->connectedAt,
            disconnectedAt: $this->disconnectedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $now,
            domainEvents: $this->domainEvents,
        );
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            connectedBy: $this->connectedBy,
            provider: $this->provider,
            providerUserId: $this->providerUserId,
            username: $this->username,
            displayName: $this->displayName,
            profilePictureUrl: $this->profilePictureUrl,
            credentials: $this->credentials,
            status: $this->status,
            lastSyncedAt: $this->lastSyncedAt,
            connectedAt: $this->connectedAt,
            disconnectedAt: $this->disconnectedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: $this->deletedAt,
        );
    }

    private function ensureNotDisconnected(): void
    {
        if ($this->status === ConnectionStatus::Disconnected) {
            throw new SocialAccountNotConnectedException;
        }
    }
}
