<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\AiSuggestionInterface;
use App\Application\Engagement\Contracts\SocialEngagementFactoryInterface;
use App\Application\Engagement\Exceptions\AutomationRuleNotFoundException;
use App\Application\Engagement\Exceptions\CommentNotFoundException;
use App\Domain\Engagement\Entities\AutomationExecution;
use App\Domain\Engagement\Repositories\AutomationExecutionRepositoryInterface;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Shared\ValueObjects\Uuid;

final class ExecuteAutomationUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly AutomationRuleRepositoryInterface $ruleRepository,
        private readonly AutomationExecutionRepositoryInterface $executionRepository,
        private readonly SocialEngagementFactoryInterface $engagementFactory,
        private readonly AiSuggestionInterface $aiSuggestion,
    ) {}

    public function execute(string $ruleId, string $commentId): void
    {
        $rule = $this->ruleRepository->findById(Uuid::fromString($ruleId));
        if ($rule === null) {
            throw new AutomationRuleNotFoundException($ruleId);
        }

        $comment = $this->commentRepository->findById(Uuid::fromString($commentId));
        if ($comment === null) {
            throw new CommentNotFoundException($commentId);
        }

        if ($comment->isReplied()) {
            return;
        }

        $responseText = null;
        $success = true;
        $errorMessage = null;

        try {
            $responseText = match ($rule->actionType) {
                ActionType::ReplyFixed, ActionType::ReplyTemplate => $rule->responseTemplate ?? '',
                ActionType::ReplyAi => $this->aiSuggestion->suggestReply($comment->text, '')[0] ?? '',
                ActionType::SendWebhook => null,
            };

            if ($rule->actionType !== ActionType::SendWebhook && $responseText !== null) {
                $adapter = $this->engagementFactory->make($comment->provider);
                $result = $adapter->replyToComment($comment->externalCommentId, $responseText);

                $replyExternalId = $result['id'] ?? null;
                $comment = $comment->replyByAutomation($responseText, $rule->id, $replyExternalId);
                $this->commentRepository->update($comment->releaseEvents());
            }
        } catch (\Throwable $e) {
            $success = false;
            $errorMessage = $e->getMessage();
        }

        $execution = AutomationExecution::create(
            organizationId: $comment->organizationId,
            automationRuleId: $rule->id,
            commentId: $comment->id,
            actionType: $rule->actionType,
            responseText: $responseText,
            success: $success,
            errorMessage: $errorMessage,
            delayApplied: $rule->delaySeconds,
        );

        $this->executionRepository->create($execution);
    }
}
