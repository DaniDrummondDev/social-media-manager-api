<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class ListExecutionsInput
{
    public function __construct(
        public string $organizationId,
        public string $ruleId,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
