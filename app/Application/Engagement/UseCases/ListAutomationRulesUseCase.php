<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\AutomationRuleOutput;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListAutomationRulesUseCase
{
    public function __construct(
        private readonly AutomationRuleRepositoryInterface $ruleRepository,
    ) {}

    /**
     * @return array<AutomationRuleOutput>
     */
    public function execute(string $organizationId): array
    {
        $orgId = Uuid::fromString($organizationId);
        $rules = $this->ruleRepository->findByOrganizationId($orgId);

        return array_map(
            fn ($rule) => AutomationRuleOutput::fromEntity($rule),
            $rules,
        );
    }
}
