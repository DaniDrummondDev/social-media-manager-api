<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Resources;

use App\Application\ClientFinance\DTOs\CostAllocationOutput;

final readonly class CostAllocationResource
{
    public function __construct(
        private CostAllocationOutput $output,
    ) {}

    public static function fromOutput(CostAllocationOutput $output): self
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
            'type' => 'cost_allocation',
            'attributes' => [
                'client_id' => $this->output->clientId,
                'resource_type' => $this->output->resourceType,
                'resource_id' => $this->output->resourceId,
                'description' => $this->output->description,
                'cost_cents' => $this->output->costCents,
                'currency' => $this->output->currency,
                'allocated_at' => $this->output->allocatedAt,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
