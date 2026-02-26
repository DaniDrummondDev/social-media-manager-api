<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\CrmConnectionOutput;

final readonly class CrmConnectionResource
{
    private function __construct(
        private CrmConnectionOutput $output,
    ) {}

    public static function fromOutput(CrmConnectionOutput $output): self
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
            'type' => 'crm_connection',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'provider' => $this->output->provider,
                'external_account_id' => $this->output->externalAccountId,
                'account_name' => $this->output->accountName,
                'status' => $this->output->status,
                'last_sync_at' => $this->output->lastSyncAt,
                'connected_by' => $this->output->connectedBy,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
                'disconnected_at' => $this->output->disconnectedAt,
            ],
        ];
    }
}
