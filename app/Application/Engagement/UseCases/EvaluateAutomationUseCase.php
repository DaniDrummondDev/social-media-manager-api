<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Exceptions\CommentNotFoundException;
use App\Domain\Engagement\Repositories\AutomationExecutionRepositoryInterface;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Engagement\Repositories\BlacklistWordRepositoryInterface;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Engagement\Services\AutomationEngine;
use App\Domain\Shared\ValueObjects\Uuid;

final class EvaluateAutomationUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly AutomationRuleRepositoryInterface $ruleRepository,
        private readonly AutomationExecutionRepositoryInterface $executionRepository,
        private readonly BlacklistWordRepositoryInterface $blacklistRepository,
        private readonly AutomationEngine $automationEngine,
    ) {}

    /**
     * @return array{rule_id: string, delay_seconds: int}|null
     */
    public function execute(string $commentId): ?array
    {
        $id = Uuid::fromString($commentId);
        $comment = $this->commentRepository->findById($id);

        if ($comment === null) {
            throw new CommentNotFoundException($commentId);
        }

        $rules = $this->ruleRepository->findActiveByOrganizationId($comment->organizationId);
        $blacklistWords = $this->blacklistRepository->findByOrganizationId($comment->organizationId);

        $matchedRule = $this->automationEngine->evaluate(
            $comment,
            $rules,
            $blacklistWords,
            fn (string $ruleId) => $this->executionRepository->countTodayByRule(Uuid::fromString($ruleId)),
        );

        if ($matchedRule === null) {
            return null;
        }

        return [
            'rule_id' => (string) $matchedRule->id,
            'delay_seconds' => $matchedRule->delaySeconds,
        ];
    }
}
