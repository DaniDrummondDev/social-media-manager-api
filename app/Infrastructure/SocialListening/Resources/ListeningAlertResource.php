<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Resources;

use App\Application\SocialListening\DTOs\ListeningAlertOutput;

final readonly class ListeningAlertResource
{
    public function __construct(
        private ListeningAlertOutput $output,
    ) {}

    public static function fromOutput(ListeningAlertOutput $output): self
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
            'type' => 'listening_alert',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'name' => $this->output->name,
                'query_ids' => $this->output->queryIds,
                'condition_type' => $this->output->conditionType,
                'threshold' => $this->output->threshold,
                'window_minutes' => $this->output->windowMinutes,
                'channels' => $this->output->channels,
                'cooldown_minutes' => $this->output->cooldownMinutes,
                'is_active' => $this->output->isActive,
                'last_triggered_at' => $this->output->lastTriggeredAt,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
