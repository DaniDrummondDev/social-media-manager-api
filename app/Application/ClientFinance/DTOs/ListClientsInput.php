<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class ListClientsInput
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $search = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
