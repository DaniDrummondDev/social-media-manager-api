<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\DeleteSafetyRuleInput;
use App\Application\AIIntelligence\Exceptions\SafetyRuleNotFoundException;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeleteSafetyRuleUseCase
{
    public function __construct(
        private readonly BrandSafetyRuleRepositoryInterface $ruleRepository,
    ) {}

    public function execute(DeleteSafetyRuleInput $input): void
    {
        $ruleId = Uuid::fromString($input->ruleId);

        $rule = $this->ruleRepository->findById($ruleId);

        if ($rule === null) {
            throw new SafetyRuleNotFoundException;
        }

        if ((string) $rule->organizationId !== $input->organizationId) {
            throw new SafetyRuleNotFoundException;
        }

        $this->ruleRepository->delete($ruleId);
    }
}
