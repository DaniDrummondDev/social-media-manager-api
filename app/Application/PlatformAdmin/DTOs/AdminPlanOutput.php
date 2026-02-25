<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class AdminPlanOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $priceMonthly,
        public int $priceYearly,
        public string $currency,
        public array $limits,
        public array $features,
        public bool $isActive,
        public int $sortOrder,
        public int $subscribersCount,
        public string $createdAt,
    ) {}
}
