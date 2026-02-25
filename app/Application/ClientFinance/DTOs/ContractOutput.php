<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

use App\Domain\ClientFinance\Entities\ClientContract;

final readonly class ContractOutput
{
    /**
     * @param  array<string>  $socialAccountIds
     */
    public function __construct(
        public string $id,
        public string $clientId,
        public string $organizationId,
        public string $name,
        public string $type,
        public int $valueCents,
        public string $currency,
        public string $startsAt,
        public ?string $endsAt,
        public array $socialAccountIds,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(ClientContract $contract): self
    {
        return new self(
            id: $contract->id->value,
            clientId: $contract->clientId->value,
            organizationId: $contract->organizationId->value,
            name: $contract->name,
            type: $contract->type->value,
            valueCents: $contract->valueCents,
            currency: $contract->currency->value,
            startsAt: $contract->startsAt->format('c'),
            endsAt: $contract->endsAt?->format('c'),
            socialAccountIds: $contract->socialAccountIds,
            status: $contract->status->value,
            createdAt: $contract->createdAt->format('c'),
            updatedAt: $contract->updatedAt->format('c'),
        );
    }
}
