<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\CrmSyncLogOutput;

final readonly class CrmSyncLogResource
{
    private function __construct(
        private CrmSyncLogOutput $output,
    ) {}

    public static function fromOutput(CrmSyncLogOutput $output): self
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
            'type' => 'crm_sync_log',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'connection_id' => $this->output->connectionId,
                'direction' => $this->output->direction,
                'entity_type' => $this->output->entityType,
                'action' => $this->output->action,
                'status' => $this->output->status,
                'external_id' => $this->output->externalId,
                'error_message' => $this->output->errorMessage,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
