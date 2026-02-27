<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

use App\Domain\PaidAdvertising\Entities\AdAccount;

final readonly class AdAccountOutput
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $provider,
        public string $providerAccountId,
        public string $providerAccountName,
        public string $status,
        public bool $isOperational,
        public string $connectedAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(AdAccount $account): self
    {
        return new self(
            id: (string) $account->id,
            organizationId: (string) $account->organizationId,
            provider: $account->provider->value,
            providerAccountId: $account->providerAccountId,
            providerAccountName: $account->providerAccountName,
            status: $account->status->value,
            isOperational: $account->isOperational(),
            connectedAt: $account->connectedAt->format('c'),
            createdAt: $account->createdAt->format('c'),
            updatedAt: $account->updatedAt->format('c'),
        );
    }
}
