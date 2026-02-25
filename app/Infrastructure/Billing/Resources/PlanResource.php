<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Resources;

use App\Application\Billing\DTOs\PlanOutput;

final readonly class PlanResource
{
    public function __construct(
        private PlanOutput $output,
    ) {}

    public static function fromOutput(PlanOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'plan',
            'attributes' => [
                'name' => $this->output->name,
                'slug' => $this->output->slug,
                'description' => $this->output->description,
                'price_monthly_cents' => $this->output->priceMonthly,
                'price_yearly_cents' => $this->output->priceYearly,
                'currency' => $this->output->currency,
                'limits' => $this->output->limits,
                'features' => $this->output->features,
                'sort_order' => $this->output->sortOrder,
            ],
        ];
    }
}
