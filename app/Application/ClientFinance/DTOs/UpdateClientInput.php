<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class UpdateClientInput
{
    /**
     * @param  array<string, string|null>|null  $billingAddress
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $clientId,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $companyName = null,
        public ?string $taxId = null,
        public ?array $billingAddress = null,
        public ?string $notes = null,
        public ?string $status = null,
    ) {}
}
