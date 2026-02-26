<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Entities;

use App\Domain\Engagement\Events\CrmConnected;
use App\Domain\Engagement\Events\CrmDisconnected;
use App\Domain\Engagement\Events\CrmTokenExpired;
use App\Domain\Engagement\Exceptions\InvalidCrmConnectionStatusTransitionException;
use App\Domain\Engagement\ValueObjects\CrmConnectionStatus;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class CrmConnection
{
    /**
     * @param  array<string, mixed>  $settings
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public CrmProvider $provider,
        public string $accessToken,
        public ?string $refreshToken,
        public ?DateTimeImmutable $tokenExpiresAt,
        public string $externalAccountId,
        public string $accountName,
        public CrmConnectionStatus $status,
        public array $settings,
        public Uuid $connectedBy,
        public ?DateTimeImmutable $lastSyncAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $disconnectedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function create(
        Uuid $organizationId,
        CrmProvider $provider,
        string $accessToken,
        ?string $refreshToken,
        ?DateTimeImmutable $tokenExpiresAt,
        string $externalAccountId,
        string $accountName,
        Uuid $connectedBy,
        array $settings = [],
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            provider: $provider,
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            tokenExpiresAt: $tokenExpiresAt,
            externalAccountId: $externalAccountId,
            accountName: $accountName,
            status: CrmConnectionStatus::Connected,
            settings: $settings,
            connectedBy: $connectedBy,
            lastSyncAt: null,
            createdAt: $now,
            updatedAt: $now,
            disconnectedAt: null,
            domainEvents: [
                new CrmConnected(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $connectedBy,
                    provider: $provider->value,
                    accountName: $accountName,
                ),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        CrmProvider $provider,
        string $accessToken,
        ?string $refreshToken,
        ?DateTimeImmutable $tokenExpiresAt,
        string $externalAccountId,
        string $accountName,
        CrmConnectionStatus $status,
        array $settings,
        Uuid $connectedBy,
        ?DateTimeImmutable $lastSyncAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $disconnectedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            provider: $provider,
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            tokenExpiresAt: $tokenExpiresAt,
            externalAccountId: $externalAccountId,
            accountName: $accountName,
            status: $status,
            settings: $settings,
            connectedBy: $connectedBy,
            lastSyncAt: $lastSyncAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            disconnectedAt: $disconnectedAt,
        );
    }

    public function disconnect(string $userId): self
    {
        $this->ensureCanTransitionTo(CrmConnectionStatus::Revoked);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            provider: $this->provider,
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            tokenExpiresAt: $this->tokenExpiresAt,
            externalAccountId: $this->externalAccountId,
            accountName: $this->accountName,
            status: CrmConnectionStatus::Revoked,
            settings: $this->settings,
            connectedBy: $this->connectedBy,
            lastSyncAt: $this->lastSyncAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            disconnectedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new CrmDisconnected(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    provider: $this->provider->value,
                ),
            ],
        );
    }

    public function refreshTokens(
        string $accessToken,
        ?string $refreshToken,
        ?DateTimeImmutable $tokenExpiresAt,
    ): self {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            provider: $this->provider,
            accessToken: $accessToken,
            refreshToken: $refreshToken ?? $this->refreshToken,
            tokenExpiresAt: $tokenExpiresAt,
            externalAccountId: $this->externalAccountId,
            accountName: $this->accountName,
            status: CrmConnectionStatus::Connected,
            settings: $this->settings,
            connectedBy: $this->connectedBy,
            lastSyncAt: $this->lastSyncAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            disconnectedAt: $this->disconnectedAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function markTokenExpired(string $userId): self
    {
        $this->ensureCanTransitionTo(CrmConnectionStatus::TokenExpired);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            provider: $this->provider,
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            tokenExpiresAt: $this->tokenExpiresAt,
            externalAccountId: $this->externalAccountId,
            accountName: $this->accountName,
            status: CrmConnectionStatus::TokenExpired,
            settings: $this->settings,
            connectedBy: $this->connectedBy,
            lastSyncAt: $this->lastSyncAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            disconnectedAt: $this->disconnectedAt,
            domainEvents: [
                ...$this->domainEvents,
                new CrmTokenExpired(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                    connectionId: (string) $this->id,
                    provider: $this->provider->value,
                ),
            ],
        );
    }

    public function markError(): self
    {
        $this->ensureCanTransitionTo(CrmConnectionStatus::Error);

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            provider: $this->provider,
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            tokenExpiresAt: $this->tokenExpiresAt,
            externalAccountId: $this->externalAccountId,
            accountName: $this->accountName,
            status: CrmConnectionStatus::Error,
            settings: $this->settings,
            connectedBy: $this->connectedBy,
            lastSyncAt: $this->lastSyncAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            disconnectedAt: $this->disconnectedAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function recordSync(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            provider: $this->provider,
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            tokenExpiresAt: $this->tokenExpiresAt,
            externalAccountId: $this->externalAccountId,
            accountName: $this->accountName,
            status: $this->status,
            settings: $this->settings,
            connectedBy: $this->connectedBy,
            lastSyncAt: new DateTimeImmutable,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            disconnectedAt: $this->disconnectedAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isTokenExpired(): bool
    {
        if ($this->status === CrmConnectionStatus::TokenExpired) {
            return true;
        }

        if ($this->tokenExpiresAt === null) {
            return false;
        }

        return new DateTimeImmutable > $this->tokenExpiresAt;
    }

    public function canSync(): bool
    {
        return $this->status->canSync();
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            provider: $this->provider,
            accessToken: $this->accessToken,
            refreshToken: $this->refreshToken,
            tokenExpiresAt: $this->tokenExpiresAt,
            externalAccountId: $this->externalAccountId,
            accountName: $this->accountName,
            status: $this->status,
            settings: $this->settings,
            connectedBy: $this->connectedBy,
            lastSyncAt: $this->lastSyncAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            disconnectedAt: $this->disconnectedAt,
        );
    }

    private function ensureCanTransitionTo(CrmConnectionStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidCrmConnectionStatusTransitionException(
                "Não é possível transicionar de '{$this->status->value}' para '{$target->value}'.",
            );
        }
    }
}
