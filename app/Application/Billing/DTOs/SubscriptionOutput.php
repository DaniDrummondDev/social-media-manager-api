<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\Entities\Subscription;

final readonly class SubscriptionOutput
{
    /**
     * @param  array{id: string, name: string, slug: string}  $plan
     */
    public function __construct(
        public string $id,
        public array $plan,
        public string $status,
        public string $billingCycle,
        public string $currentPeriodStart,
        public string $currentPeriodEnd,
        public ?string $trialEndsAt,
        public ?string $canceledAt,
        public bool $cancelAtPeriodEnd,
        public string $createdAt,
    ) {}

    public static function fromEntity(Subscription $subscription, Plan $plan): self
    {
        return new self(
            id: (string) $subscription->id,
            plan: [
                'id' => (string) $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
            ],
            status: $subscription->status->value,
            billingCycle: $subscription->billingCycle->value,
            currentPeriodStart: $subscription->currentPeriodStart->format('c'),
            currentPeriodEnd: $subscription->currentPeriodEnd->format('c'),
            trialEndsAt: $subscription->trialEndsAt?->format('c'),
            canceledAt: $subscription->canceledAt?->format('c'),
            cancelAtPeriodEnd: $subscription->cancelAtPeriodEnd,
            createdAt: $subscription->createdAt->format('c'),
        );
    }
}
