<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Resources;

use App\Application\PlatformAdmin\DTOs\AdminPlanOutput;

final readonly class AdminPlanResource
{
    private function __construct(
        private AdminPlanOutput $output,
    ) {}

    public static function fromOutput(AdminPlanOutput $output): self
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
                'is_active' => $this->output->isActive,
                'sort_order' => $this->output->sortOrder,
                'subscribers_count' => $this->output->subscribersCount,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
