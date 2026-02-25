<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

use App\Domain\ClientFinance\Entities\Client;

final readonly class ClientOutput
{
    /**
     * @param  array<string, string|null>|null  $billingAddress
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $companyName,
        public ?string $taxId,
        public ?string $taxIdType,
        public ?array $billingAddress,
        public ?string $notes,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Client $client): self
    {
        return new self(
            id: $client->id->value,
            organizationId: $client->organizationId->value,
            name: $client->name,
            email: $client->email,
            phone: $client->phone,
            companyName: $client->companyName,
            taxId: $client->taxId?->value,
            taxIdType: $client->taxId?->type,
            billingAddress: $client->billingAddress?->toArray(),
            notes: $client->notes,
            status: $client->status->value,
            createdAt: $client->createdAt->format('c'),
            updatedAt: $client->updatedAt->format('c'),
        );
    }
}
