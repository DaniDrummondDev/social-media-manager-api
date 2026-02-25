<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class UsageOutput
{
    /**
     * @param  array<string, array{used: int, limit: int, percentage: float|null}>  $usage
     */
    public function __construct(
        public string $plan,
        public string $billingCycle,
        public string $currentPeriodEnd,
        public array $usage,
    ) {}
}
