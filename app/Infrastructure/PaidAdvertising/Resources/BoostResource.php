<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Resources;

use App\Application\PaidAdvertising\DTOs\BoostOutput;

final readonly class BoostResource
{
    private function __construct(
        private BoostOutput $output,
    ) {}

    public static function fromOutput(BoostOutput $output): self
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
            'type' => 'ad_boost',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'scheduled_post_id' => $this->output->scheduledPostId,
                'ad_account_id' => $this->output->adAccountId,
                'audience_id' => $this->output->audienceId,
                'budget_amount_cents' => $this->output->budgetAmountCents,
                'budget_currency' => $this->output->budgetCurrency,
                'budget_type' => $this->output->budgetType,
                'duration_days' => $this->output->durationDays,
                'objective' => $this->output->objective,
                'status' => $this->output->status,
                'external_ids' => $this->output->externalIds,
                'rejection_reason' => $this->output->rejectionReason,
                'started_at' => $this->output->startedAt,
                'completed_at' => $this->output->completedAt,
                'created_by' => $this->output->createdBy,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
