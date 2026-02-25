<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class UpdateContractInput
{
    /**
     * @param  array<string>|null  $socialAccountIds
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $contractId,
        public ?string $name = null,
        public ?int $valueCents = null,
        public ?string $endsAt = null,
        public ?array $socialAccountIds = null,
    ) {}
}
