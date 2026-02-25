<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class CreateContractInput
{
    /**
     * @param  array<string>  $socialAccountIds
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $clientId,
        public string $name,
        public string $type,
        public int $valueCents,
        public string $currency,
        public string $startsAt,
        public ?string $endsAt = null,
        public array $socialAccountIds = [],
    ) {}
}
