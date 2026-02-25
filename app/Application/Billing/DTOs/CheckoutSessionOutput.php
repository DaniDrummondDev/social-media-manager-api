<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class CheckoutSessionOutput
{
    public function __construct(
        public string $checkoutUrl,
        public string $sessionId,
        public string $expiresAt,
    ) {}
}
