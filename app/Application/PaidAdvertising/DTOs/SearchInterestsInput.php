<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class SearchInterestsInput
{
    public function __construct(
        public string $organizationId,
        public string $accountId,
        public string $query,
        public int $limit = 25,
    ) {}
}
