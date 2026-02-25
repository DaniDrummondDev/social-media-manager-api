<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Resources;

use App\Application\Billing\DTOs\SubscriptionOutput;

final readonly class SubscriptionResource
{
    public function __construct(
        private SubscriptionOutput $output,
    ) {}

    public static function fromOutput(SubscriptionOutput $output): self
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
            'type' => 'subscription',
            'attributes' => [
                'plan' => $this->output->plan,
                'status' => $this->output->status,
                'billing_cycle' => $this->output->billingCycle,
                'current_period_start' => $this->output->currentPeriodStart,
                'current_period_end' => $this->output->currentPeriodEnd,
                'trial_ends_at' => $this->output->trialEndsAt,
                'canceled_at' => $this->output->canceledAt,
                'cancel_at_period_end' => $this->output->cancelAtPeriodEnd,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
