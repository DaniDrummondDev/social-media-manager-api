<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class PortalSessionOutput
{
    public function __construct(
        public string $portalUrl,
    ) {}
}
