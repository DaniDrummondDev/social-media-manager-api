<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\AutomationRuleOutput;
use App\Application\Engagement\DTOs\UpdateAutomationRuleInput;
use App\Application\Engagement\Exceptions\AutomationRuleNotFoundException;
use App\Domain\Engagement\Exceptions\InvalidAutomationRuleException;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Engagement\ValueObjects\ConditionOperator;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateAutomationRuleUseCase
{
    public function __construct(
        private readonly AutomationRuleRepositoryInterface $ruleRepository,
    ) {}

    public function execute(UpdateAutomationRuleInput $input): AutomationRuleOutput
    {
        $ruleId = Uuid::fromString($input->ruleId);
        $rule = $this->ruleRepository->findById($ruleId);

        if ($rule === null || (string) $rule->organizationId !== $input->organizationId) {
            throw new AutomationRuleNotFoundException($input->ruleId);
        }

        if ($input->priority !== null && $input->priority !== $rule->priority) {
            $organizationId = Uuid::fromString($input->organizationId);
            if ($this->ruleRepository->isPriorityTaken($organizationId, $input->priority, $ruleId)) {
                throw new InvalidAutomationRuleException(
                    "Prioridade {$input->priority} já está em uso.",
                );
            }
        }

        $conditions = null;
        if ($input->conditions !== null) {
            $conditions = array_map(fn (array $c) => new RuleCondition(
                field: $c['field'],
                operator: ConditionOperator::from($c['operator']),
                value: $c['value'],
                isCaseSensitive: (bool) ($c['is_case_sensitive'] ?? false),
                position: (int) ($c['position'] ?? 0),
            ), $input->conditions);
        }

        $rule = $rule->update(
            name: $input->name,
            priority: $input->priority,
            conditions: $conditions,
            actionType: $input->actionType !== null ? ActionType::from($input->actionType) : null,
            responseTemplate: $input->responseTemplate,
            webhookId: $input->webhookId !== null ? Uuid::fromString($input->webhookId) : null,
            delaySeconds: $input->delaySeconds,
            dailyLimit: $input->dailyLimit,
            appliesToNetworks: $input->appliesToNetworks,
            appliesToCampaigns: $input->appliesToCampaigns,
        );

        $this->ruleRepository->update($rule);

        return AutomationRuleOutput::fromEntity($rule);
    }
}
