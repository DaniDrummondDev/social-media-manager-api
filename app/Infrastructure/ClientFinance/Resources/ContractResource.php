<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Resources;

use App\Application\ClientFinance\DTOs\ContractOutput;

final readonly class ContractResource
{
    public function __construct(
        private ContractOutput $output,
    ) {}

    public static function fromOutput(ContractOutput $output): self
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
            'type' => 'contract',
            'attributes' => [
                'client_id' => $this->output->clientId,
                'name' => $this->output->name,
                'type' => $this->output->type,
                'value_cents' => $this->output->valueCents,
                'currency' => $this->output->currency,
                'starts_at' => $this->output->startsAt,
                'ends_at' => $this->output->endsAt,
                'social_account_ids' => $this->output->socialAccountIds,
                'status' => $this->output->status,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
