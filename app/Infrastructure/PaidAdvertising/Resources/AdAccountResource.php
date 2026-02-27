<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Resources;

use App\Application\PaidAdvertising\DTOs\AdAccountOutput;

final readonly class AdAccountResource
{
    private function __construct(
        private AdAccountOutput $output,
    ) {}

    public static function fromOutput(AdAccountOutput $output): self
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
            'type' => 'ad_account',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'provider' => $this->output->provider,
                'provider_account_id' => $this->output->providerAccountId,
                'provider_account_name' => $this->output->providerAccountName,
                'status' => $this->output->status,
                'is_operational' => $this->output->isOperational,
                'connected_at' => $this->output->connectedAt,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
