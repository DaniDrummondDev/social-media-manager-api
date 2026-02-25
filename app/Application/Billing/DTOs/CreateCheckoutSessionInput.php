<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class CreateCheckoutSessionInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $planSlug,
        public string $billingCycle,
        public string $successUrl,
        public string $cancelUrl,
    ) {}
}
