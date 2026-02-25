<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\WebhookDeliveryOutput;

final readonly class WebhookDeliveryResource
{
    private function __construct(
        private WebhookDeliveryOutput $output,
    ) {}

    public static function fromOutput(WebhookDeliveryOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'webhook_delivery',
            'attributes' => [
                'webhook_endpoint_id' => $this->output->webhookEndpointId,
                'event' => $this->output->event,
                'payload' => $this->output->payload,
                'response_status' => $this->output->responseStatus,
                'response_body' => $this->output->responseBody,
                'response_time_ms' => $this->output->responseTimeMs,
                'attempts' => $this->output->attempts,
                'max_attempts' => $this->output->maxAttempts,
                'next_retry_at' => $this->output->nextRetryAt,
                'delivered_at' => $this->output->deliveredAt,
                'failed_at' => $this->output->failedAt,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
