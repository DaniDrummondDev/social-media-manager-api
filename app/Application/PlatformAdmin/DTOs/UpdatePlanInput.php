<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class UpdatePlanInput
{
    public function __construct(
        public string $planId,
        public ?string $name = null,
        public ?string $description = null,
        public ?int $priceMonthly = null,
        public ?int $priceYearly = null,
        public ?array $limits = null,
        public ?array $features = null,
        public ?int $sortOrder = null,
    ) {}
}
