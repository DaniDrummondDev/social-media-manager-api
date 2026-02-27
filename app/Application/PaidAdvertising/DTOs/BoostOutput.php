<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

use App\Domain\PaidAdvertising\Entities\AdBoost;

final readonly class BoostOutput
{
    /**
     * @param  array<string, string>|null  $externalIds
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $scheduledPostId,
        public string $adAccountId,
        public string $audienceId,
        public int $budgetAmountCents,
        public string $budgetCurrency,
        public string $budgetType,
        public int $durationDays,
        public string $objective,
        public string $status,
        public ?array $externalIds,
        public ?string $rejectionReason,
        public ?string $startedAt,
        public ?string $completedAt,
        public string $createdBy,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(AdBoost $boost): self
    {
        return new self(
            id: (string) $boost->id,
            organizationId: (string) $boost->organizationId,
            scheduledPostId: (string) $boost->scheduledPostId,
            adAccountId: (string) $boost->adAccountId,
            audienceId: (string) $boost->audienceId,
            budgetAmountCents: $boost->budget->amountCents,
            budgetCurrency: $boost->budget->currency,
            budgetType: $boost->budget->type->value,
            durationDays: $boost->durationDays,
            objective: $boost->objective->value,
            status: $boost->status->value,
            externalIds: $boost->externalIds,
            rejectionReason: $boost->rejectionReason,
            startedAt: $boost->startedAt?->format('c'),
            completedAt: $boost->completedAt?->format('c'),
            createdBy: (string) $boost->createdBy,
            createdAt: $boost->createdAt->format('c'),
            updatedAt: $boost->updatedAt->format('c'),
        );
    }
}
