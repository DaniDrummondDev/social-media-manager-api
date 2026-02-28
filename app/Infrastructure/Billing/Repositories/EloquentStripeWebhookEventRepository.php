<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Repositories;

use App\Domain\Billing\Repositories\StripeWebhookEventRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Billing\Models\StripeWebhookEventModel;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentStripeWebhookEventRepository implements StripeWebhookEventRepositoryInterface
{
    public function __construct(
        private readonly StripeWebhookEventModel $model,
    ) {}

    public function existsByStripeEventId(string $stripeEventId): bool
    {
        return $this->model->newQuery()
            ->where('stripe_event_id', $stripeEventId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $stripeEventId, string $eventType, array $payload): void
    {
        $this->model->newQuery()->create([
            'id' => (string) Uuid::generate(),
            'stripe_event_id' => $stripeEventId,
            'event_type' => $eventType,
            'processed' => false,
            'payload' => $payload,
        ]);
    }

    public function createIfNotExists(string $stripeEventId, string $eventType, array $payload): bool
    {
        try {
            $this->create($stripeEventId, $eventType, $payload);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    public function markProcessed(string $stripeEventId, ?string $errorMessage = null): void
    {
        $this->model->newQuery()
            ->where('stripe_event_id', $stripeEventId)
            ->update([
                'processed' => true,
                'processed_at' => now()->format('Y-m-d H:i:s'),
                'error_message' => $errorMessage,
            ]);
    }
}
