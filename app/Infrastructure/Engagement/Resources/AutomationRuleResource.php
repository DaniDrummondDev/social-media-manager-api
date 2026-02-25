<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\AutomationRuleOutput;

final readonly class AutomationRuleResource
{
    private function __construct(
        private AutomationRuleOutput $output,
    ) {}

    public static function fromOutput(AutomationRuleOutput $output): self
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
            'type' => 'automation_rule',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'name' => $this->output->name,
                'priority' => $this->output->priority,
                'conditions' => $this->output->conditions,
                'action_type' => $this->output->actionType,
                'response_template' => $this->output->responseTemplate,
                'webhook_id' => $this->output->webhookId,
                'delay_seconds' => $this->output->delaySeconds,
                'daily_limit' => $this->output->dailyLimit,
                'is_active' => $this->output->isActive,
                'applies_to_networks' => $this->output->appliesToNetworks,
                'applies_to_campaigns' => $this->output->appliesToCampaigns,
                'executions_count' => $this->output->executionsCount,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
