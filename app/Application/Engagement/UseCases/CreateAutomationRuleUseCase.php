<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\AutomationRuleOutput;
use App\Application\Engagement\DTOs\CreateAutomationRuleInput;
use App\Domain\Engagement\Entities\AutomationRule;
use App\Domain\Engagement\Exceptions\InvalidAutomationRuleException;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Engagement\ValueObjects\ConditionOperator;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateAutomationRuleUseCase
{
    public function __construct(
        private readonly AutomationRuleRepositoryInterface $ruleRepository,
    ) {}

    public function execute(CreateAutomationRuleInput $input): AutomationRuleOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        if ($this->ruleRepository->isPriorityTaken($organizationId, $input->priority)) {
            throw new InvalidAutomationRuleException(
                "Prioridade {$input->priority} já está em uso.",
            );
        }

        $conditions = array_map(fn (array $c) => new RuleCondition(
            field: $c['field'],
            operator: ConditionOperator::from($c['operator']),
            value: $c['value'],
            isCaseSensitive: (bool) ($c['is_case_sensitive'] ?? false),
            position: (int) ($c['position'] ?? 0),
        ), $input->conditions);

        $rule = AutomationRule::create(
            organizationId: $organizationId,
            name: $input->name,
            priority: $input->priority,
            conditions: $conditions,
            actionType: ActionType::from($input->actionType),
            responseTemplate: $input->responseTemplate,
            webhookId: $input->webhookId !== null ? Uuid::fromString($input->webhookId) : null,
            delaySeconds: $input->delaySeconds,
            dailyLimit: $input->dailyLimit,
            appliesToNetworks: $input->appliesToNetworks,
            appliesToCampaigns: $input->appliesToCampaigns,
        );

        $this->ruleRepository->create($rule);

        return AutomationRuleOutput::fromEntity($rule);
    }
}
