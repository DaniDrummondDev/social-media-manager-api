<?php

declare(strict_types=1);

namespace App\Domain\Billing\Repositories;

interface StripeWebhookEventRepositoryInterface
{
    public function existsByStripeEventId(string $stripeEventId): bool;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $stripeEventId, string $eventType, array $payload): void;

    /**
     * Atomically insert a webhook event. Returns false if the event already exists.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createIfNotExists(string $stripeEventId, string $eventType, array $payload): bool;

    public function markProcessed(string $stripeEventId, ?string $errorMessage = null): void;
}
