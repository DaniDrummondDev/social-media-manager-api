<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\Entities\AutomationExecution;

final readonly class AutomationExecutionOutput
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $automationRuleId,
        public string $commentId,
        public string $actionType,
        public ?string $responseText,
        public bool $success,
        public ?string $errorMessage,
        public int $delayApplied,
        public string $executedAt,
    ) {}

    public static function fromEntity(AutomationExecution $execution): self
    {
        return new self(
            id: (string) $execution->id,
            organizationId: (string) $execution->organizationId,
            automationRuleId: (string) $execution->automationRuleId,
            commentId: (string) $execution->commentId,
            actionType: $execution->actionType->value,
            responseText: $execution->responseText,
            success: $execution->success,
            errorMessage: $execution->errorMessage,
            delayApplied: $execution->delayApplied,
            executedAt: $execution->executedAt->format('c'),
        );
    }
}
