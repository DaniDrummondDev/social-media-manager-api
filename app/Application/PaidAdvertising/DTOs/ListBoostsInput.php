<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class ListBoostsInput
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
