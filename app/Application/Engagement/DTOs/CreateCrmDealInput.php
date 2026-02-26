<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class CreateCrmDealInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $connectionId,
        public string $dealName,
        public ?string $contactExternalId = null,
        public ?float $amount = null,
        public ?string $stage = null,
        public ?string $campaignId = null,
    ) {}
}
