<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\AutomationExecutionOutput;

final readonly class AutomationExecutionResource
{
    private function __construct(
        private AutomationExecutionOutput $output,
    ) {}

    public static function fromOutput(AutomationExecutionOutput $output): self
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
            'type' => 'automation_execution',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'automation_rule_id' => $this->output->automationRuleId,
                'comment_id' => $this->output->commentId,
                'action_type' => $this->output->actionType,
                'response_text' => $this->output->responseText,
                'success' => $this->output->success,
                'error_message' => $this->output->errorMessage,
                'delay_applied' => $this->output->delayApplied,
                'executed_at' => $this->output->executedAt,
            ],
        ];
    }
}
