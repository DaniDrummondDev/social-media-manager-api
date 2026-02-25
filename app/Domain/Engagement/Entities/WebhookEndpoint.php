<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Entities;

use App\Domain\Engagement\Events\WebhookEndpointCreated;
use App\Domain\Engagement\ValueObjects\WebhookSecret;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class WebhookEndpoint
{
    /**
     * @param  array<string>  $events
     * @param  array<string, string>|null  $headers
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $name,
        public string $url,
        public WebhookSecret $secret,
        public array $events,
        public ?array $headers,
        public bool $isActive,
        public ?DateTimeImmutable $lastDeliveryAt,
        public ?int $lastDeliveryStatus,
        public int $failureCount,
        public ?DateTimeImmutable $deletedAt,
        public ?DateTimeImmutable $purgeAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string>  $events
     * @param  array<string, string>|null  $headers
     */
    public static function create(
        Uuid $organizationId,
        string $name,
        string $url,
        array $events,
        ?array $headers = null,
        string $userId = 'system',
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            url: $url,
            secret: WebhookSecret::generate(),
            events: $events,
            headers: $headers,
            isActive: true,
            lastDeliveryAt: null,
            lastDeliveryStatus: null,
            failureCount: 0,
            deletedAt: null,
            purgeAt: null,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new WebhookEndpointCreated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: $userId,
                    webhookName: $name,
                ),
            ],
        );
    }

    /**
     * @param  array<string>  $events
     * @param  array<string, string>|null  $headers
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $name,
        string $url,
        WebhookSecret $secret,
        array $events,
        ?array $headers,
        bool $isActive,
        ?DateTimeImmutable $lastDeliveryAt,
        ?int $lastDeliveryStatus,
        int $failureCount,
        ?DateTimeImmutable $deletedAt,
        ?DateTimeImmutable $purgeAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            url: $url,
            secret: $secret,
            events: $events,
            headers: $headers,
            isActive: $isActive,
            lastDeliveryAt: $lastDeliveryAt,
            lastDeliveryStatus: $lastDeliveryStatus,
            failureCount: $failureCount,
            deletedAt: $deletedAt,
            purgeAt: $purgeAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param  array<string>|null  $events
     * @param  array<string, string>|null  $headers
     */
    public function update(
        ?string $name = null,
        ?string $url = null,
        ?array $events = null,
        ?array $headers = null,
        ?bool $isActive = null,
    ): self {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $name ?? $this->name,
            url: $url ?? $this->url,
            secret: $this->secret,
            events: $events ?? $this->events,
            headers: $headers ?? $this->headers,
            isActive: $isActive ?? $this->isActive,
            lastDeliveryAt: $this->lastDeliveryAt,
            lastDeliveryStatus: $this->lastDeliveryStatus,
            failureCount: $this->failureCount,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function softDelete(): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            url: $this->url,
            secret: $this->secret,
            events: $this->events,
            headers: $this->headers,
            isActive: false,
            lastDeliveryAt: $this->lastDeliveryAt,
            lastDeliveryStatus: $this->lastDeliveryStatus,
            failureCount: $this->failureCount,
            deletedAt: $now,
            purgeAt: $now->modify('+30 days'),
            createdAt: $this->createdAt,
            updatedAt: $now,
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            url: $this->url,
            secret: $this->secret,
            events: $this->events,
            headers: $this->headers,
            isActive: false,
            lastDeliveryAt: $this->lastDeliveryAt,
            lastDeliveryStatus: $this->lastDeliveryStatus,
            failureCount: $this->failureCount,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function recordDelivery(int $status): self
    {
        $isSuccess = $status >= 200 && $status < 300;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            url: $this->url,
            secret: $this->secret,
            events: $this->events,
            headers: $this->headers,
            isActive: $this->isActive,
            lastDeliveryAt: new DateTimeImmutable,
            lastDeliveryStatus: $status,
            failureCount: $isSuccess ? 0 : $this->failureCount + 1,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function shouldAutoDeactivate(): bool
    {
        return $this->failureCount >= 10;
    }
}
