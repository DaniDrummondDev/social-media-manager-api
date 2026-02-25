<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class DashboardOutput
{
    public function __construct(
        public array $overview,
        public array $subscriptions,
        public array $usage,
        public array $health,
        public string $generatedAt,
    ) {}
}
