<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class CreateBoostInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $scheduledPostId,
        public string $adAccountId,
        public string $audienceId,
        public int $budgetAmountCents,
        public string $budgetCurrency,
        public string $budgetType,
        public int $durationDays,
        public string $objective,
    ) {}
}
