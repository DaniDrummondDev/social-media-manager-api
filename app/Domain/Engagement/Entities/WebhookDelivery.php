<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Entities;

use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class WebhookDelivery
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Uuid $id,
        public Uuid $webhookEndpointId,
        public string $event,
        public array $payload,
        public ?int $responseStatus,
        public ?string $responseBody,
        public ?int $responseTimeMs,
        public int $attempts,
        public int $maxAttempts,
        public ?DateTimeImmutable $nextRetryAt,
        public ?DateTimeImmutable $deliveredAt,
        public ?DateTimeImmutable $failedAt,
        public DateTimeImmutable $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function create(
        Uuid $webhookEndpointId,
        string $event,
        array $payload,
        int $maxAttempts = 4,
    ): self {
        return new self(
            id: Uuid::generate(),
            webhookEndpointId: $webhookEndpointId,
            event: $event,
            payload: $payload,
            responseStatus: null,
            responseBody: null,
            responseTimeMs: null,
            attempts: 0,
            maxAttempts: $maxAttempts,
            nextRetryAt: null,
            deliveredAt: null,
            failedAt: null,
            createdAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $webhookEndpointId,
        string $event,
        array $payload,
        ?int $responseStatus,
        ?string $responseBody,
        ?int $responseTimeMs,
        int $attempts,
        int $maxAttempts,
        ?DateTimeImmutable $nextRetryAt,
        ?DateTimeImmutable $deliveredAt,
        ?DateTimeImmutable $failedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            webhookEndpointId: $webhookEndpointId,
            event: $event,
            payload: $payload,
            responseStatus: $responseStatus,
            responseBody: $responseBody,
            responseTimeMs: $responseTimeMs,
            attempts: $attempts,
            maxAttempts: $maxAttempts,
            nextRetryAt: $nextRetryAt,
            deliveredAt: $deliveredAt,
            failedAt: $failedAt,
            createdAt: $createdAt,
        );
    }

    public function markAsDelivered(int $status, ?string $body, int $timeMs): self
    {
        return new self(
            id: $this->id,
            webhookEndpointId: $this->webhookEndpointId,
            event: $this->event,
            payload: $this->payload,
            responseStatus: $status,
            responseBody: $body,
            responseTimeMs: $timeMs,
            attempts: $this->attempts + 1,
            maxAttempts: $this->maxAttempts,
            nextRetryAt: null,
            deliveredAt: new DateTimeImmutable,
            failedAt: null,
            createdAt: $this->createdAt,
        );
    }

    public function markAsFailed(int $status, ?string $body, int $timeMs): self
    {
        $attempts = $this->attempts + 1;
        $nextRetryAt = null;
        $failedAt = null;

        if ($this->shouldRetryAfterAttempt($attempts, $status)) {
            $delay = $this->retryDelayForAttempt($attempts);
            $nextRetryAt = (new DateTimeImmutable)->modify("+{$delay} seconds");
        } else {
            $failedAt = new DateTimeImmutable;
        }

        return new self(
            id: $this->id,
            webhookEndpointId: $this->webhookEndpointId,
            event: $this->event,
            payload: $this->payload,
            responseStatus: $status,
            responseBody: $body,
            responseTimeMs: $timeMs,
            attempts: $attempts,
            maxAttempts: $this->maxAttempts,
            nextRetryAt: $nextRetryAt,
            deliveredAt: null,
            failedAt: $failedAt,
            createdAt: $this->createdAt,
        );
    }

    public function shouldRetry(): bool
    {
        if ($this->deliveredAt !== null || $this->failedAt !== null) {
            return false;
        }

        if ($this->responseStatus !== null && $this->responseStatus >= 400 && $this->responseStatus < 500) {
            return false;
        }

        return $this->attempts < $this->maxAttempts;
    }

    public function nextRetryDelay(): int
    {
        return $this->retryDelayForAttempt($this->attempts + 1);
    }

    private function shouldRetryAfterAttempt(int $attempts, int $status): bool
    {
        if ($status >= 400 && $status < 500) {
            return false;
        }

        return $attempts < $this->maxAttempts;
    }

    private function retryDelayForAttempt(int $attempt): int
    {
        return match ($attempt) {
            2 => 60,
            3 => 300,
            4 => 1800,
            default => 60,
        };
    }
}
