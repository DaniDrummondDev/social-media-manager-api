<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Exceptions\AutomationRuleNotFoundException;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeleteAutomationRuleUseCase
{
    public function __construct(
        private readonly AutomationRuleRepositoryInterface $ruleRepository,
    ) {}

    public function execute(string $organizationId, string $ruleId): void
    {
        $id = Uuid::fromString($ruleId);
        $rule = $this->ruleRepository->findById($id);

        if ($rule === null || (string) $rule->organizationId !== $organizationId) {
            throw new AutomationRuleNotFoundException($ruleId);
        }

        $rule = $rule->softDelete();
        $this->ruleRepository->update($rule);
    }
}
