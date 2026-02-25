<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

final readonly class ListExportsInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
    ) {}
}
