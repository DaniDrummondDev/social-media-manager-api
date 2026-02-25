<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\Entities\WebhookEndpoint;

final readonly class WebhookOutput
{
    /**
     * @param  array<string>  $events
     * @param  array<string, string>|null  $headers
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public string $url,
        public ?string $secret,
        public array $events,
        public ?array $headers,
        public bool $isActive,
        public ?string $lastDeliveryAt,
        public ?int $lastDeliveryStatus,
        public int $failureCount,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(WebhookEndpoint $endpoint, bool $includeSecret = false): self
    {
        return new self(
            id: (string) $endpoint->id,
            organizationId: (string) $endpoint->organizationId,
            name: $endpoint->name,
            url: $endpoint->url,
            secret: $includeSecret ? (string) $endpoint->secret : null,
            events: $endpoint->events,
            headers: $endpoint->headers,
            isActive: $endpoint->isActive,
            lastDeliveryAt: $endpoint->lastDeliveryAt?->format('c'),
            lastDeliveryStatus: $endpoint->lastDeliveryStatus,
            failureCount: $endpoint->failureCount,
            createdAt: $endpoint->createdAt->format('c'),
            updatedAt: $endpoint->updatedAt->format('c'),
        );
    }
}
