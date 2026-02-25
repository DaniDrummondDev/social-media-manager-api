<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class ProcessStripeWebhookInput
{
    public function __construct(
        public string $payload,
        public string $signature,
    ) {}
}
