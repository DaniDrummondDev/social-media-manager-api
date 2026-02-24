<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

final readonly class SwitchOrganizationInput
{
    public function __construct(
        public string $userId,
        public string $organizationId,
    ) {}
}
