<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class CreatePlanInput
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description,
        public int $priceMonthly,
        public int $priceYearly,
        public string $currency = 'BRL',
        public array $limits = [],
        public array $features = [],
        public int $sortOrder = 0,
    ) {}
}
