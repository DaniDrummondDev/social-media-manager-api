<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

use App\Domain\Billing\Entities\Plan;

final readonly class PlanOutput
{
    /**
     * @param  array<string, int>  $limits
     * @param  array<string, bool>  $features
     */
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
        public int $sortOrder,
    ) {}

    public static function fromEntity(Plan $plan): self
    {
        return new self(
            id: (string) $plan->id,
            name: $plan->name,
            slug: $plan->slug,
            description: $plan->description,
            priceMonthly: $plan->priceMonthly->amountCents,
            priceYearly: $plan->priceYearly->amountCents,
            currency: $plan->priceMonthly->currency,
            limits: $plan->limits->toArray(),
            features: $plan->features->toArray(),
            sortOrder: $plan->sortOrder,
        );
    }
}
