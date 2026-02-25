<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Resources;

use App\Application\Billing\DTOs\UsageOutput;

final readonly class UsageResource
{
    public function __construct(
        private UsageOutput $output,
    ) {}

    public static function fromOutput(UsageOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'usage',
            'attributes' => [
                'plan' => $this->output->plan,
                'billing_cycle' => $this->output->billingCycle,
                'current_period_end' => $this->output->currentPeriodEnd,
                'usage' => $this->output->usage,
            ],
        ];
    }
}
