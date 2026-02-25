<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Resources;

use App\Application\ClientFinance\DTOs\ClientOutput;

final readonly class ClientResource
{
    public function __construct(
        private ClientOutput $output,
    ) {}

    public static function fromOutput(ClientOutput $output): self
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
            'type' => 'client',
            'attributes' => [
                'name' => $this->output->name,
                'email' => $this->output->email,
                'phone' => $this->output->phone,
                'company_name' => $this->output->companyName,
                'tax_id' => $this->output->taxId,
                'tax_id_type' => $this->output->taxIdType,
                'billing_address' => $this->output->billingAddress,
                'notes' => $this->output->notes,
                'status' => $this->output->status,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
