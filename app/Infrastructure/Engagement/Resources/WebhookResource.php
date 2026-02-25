<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\WebhookOutput;

final readonly class WebhookResource
{
    private function __construct(
        private WebhookOutput $output,
    ) {}

    public static function fromOutput(WebhookOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $attributes = [
            'organization_id' => $this->output->organizationId,
            'name' => $this->output->name,
            'url' => $this->output->url,
            'events' => $this->output->events,
            'headers' => $this->output->headers,
            'is_active' => $this->output->isActive,
            'last_delivery_at' => $this->output->lastDeliveryAt,
            'last_delivery_status' => $this->output->lastDeliveryStatus,
            'failure_count' => $this->output->failureCount,
            'created_at' => $this->output->createdAt,
            'updated_at' => $this->output->updatedAt,
        ];

        if ($this->output->secret !== null) {
            $attributes['secret'] = $this->output->secret;
        }

        return [
            'id' => $this->output->id,
            'type' => 'webhook_endpoint',
            'attributes' => $attributes,
        ];
    }
}
