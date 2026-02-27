<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class GetAdAccountStatusInput
{
    public function __construct(
        public string $organizationId,
        public string $accountId,
    ) {}
}
