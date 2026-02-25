<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Entities;

use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AutomationExecution
{
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $automationRuleId,
        public Uuid $commentId,
        public ActionType $actionType,
        public ?string $responseText,
        public bool $success,
        public ?string $errorMessage,
        public int $delayApplied,
        public DateTimeImmutable $executedAt,
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $automationRuleId,
        Uuid $commentId,
        ActionType $actionType,
        ?string $responseText,
        bool $success,
        ?string $errorMessage,
        int $delayApplied,
    ): self {
        return new self(
            id: Uuid::generate(),
            organizationId: $organizationId,
            automationRuleId: $automationRuleId,
            commentId: $commentId,
            actionType: $actionType,
            responseText: $responseText,
            success: $success,
            errorMessage: $errorMessage,
            delayApplied: $delayApplied,
            executedAt: new DateTimeImmutable,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $automationRuleId,
        Uuid $commentId,
        ActionType $actionType,
        ?string $responseText,
        bool $success,
        ?string $errorMessage,
        int $delayApplied,
        DateTimeImmutable $executedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            automationRuleId: $automationRuleId,
            commentId: $commentId,
            actionType: $actionType,
            responseText: $responseText,
            success: $success,
            errorMessage: $errorMessage,
            delayApplied: $delayApplied,
            executedAt: $executedAt,
        );
    }
}
