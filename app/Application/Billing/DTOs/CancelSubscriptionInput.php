<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class CancelSubscriptionInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public ?string $reason = null,
        public ?string $feedback = null,
    ) {}
}
