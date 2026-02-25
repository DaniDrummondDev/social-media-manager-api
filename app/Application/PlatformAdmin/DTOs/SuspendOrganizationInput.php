<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class SuspendOrganizationInput
{
    public function __construct(
        public string $organizationId,
        public string $reason,
    ) {}
}
