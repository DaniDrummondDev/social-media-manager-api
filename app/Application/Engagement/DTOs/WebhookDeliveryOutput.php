<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\Entities\WebhookDelivery;

final readonly class WebhookDeliveryOutput
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $id,
        public string $webhookEndpointId,
        public string $event,
        public array $payload,
        public ?int $responseStatus,
        public ?string $responseBody,
        public ?int $responseTimeMs,
        public int $attempts,
        public int $maxAttempts,
        public ?string $nextRetryAt,
        public ?string $deliveredAt,
        public ?string $failedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(WebhookDelivery $delivery): self
    {
        return new self(
            id: (string) $delivery->id,
            webhookEndpointId: (string) $delivery->webhookEndpointId,
            event: $delivery->event,
            payload: $delivery->payload,
            responseStatus: $delivery->responseStatus,
            responseBody: $delivery->responseBody,
            responseTimeMs: $delivery->responseTimeMs,
            attempts: $delivery->attempts,
            maxAttempts: $delivery->maxAttempts,
            nextRetryAt: $delivery->nextRetryAt?->format('c'),
            deliveredAt: $delivery->deliveredAt?->format('c'),
            failedAt: $delivery->failedAt?->format('c'),
            createdAt: $delivery->createdAt->format('c'),
        );
    }
}
