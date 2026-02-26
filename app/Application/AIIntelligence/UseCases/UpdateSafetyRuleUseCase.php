<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\SafetyRuleOutput;
use App\Application\AIIntelligence\DTOs\UpdateSafetyRuleInput;
use App\Application\AIIntelligence\Exceptions\SafetyRuleNotFoundException;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyRuleType;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateSafetyRuleUseCase
{
    public function __construct(
        private readonly BrandSafetyRuleRepositoryInterface $ruleRepository,
    ) {}

    public function execute(UpdateSafetyRuleInput $input): SafetyRuleOutput
    {
        $ruleId = Uuid::fromString($input->ruleId);

        $rule = $this->ruleRepository->findById($ruleId);

        if ($rule === null) {
            throw new SafetyRuleNotFoundException;
        }

        if ((string) $rule->organizationId !== $input->organizationId) {
            throw new SafetyRuleNotFoundException;
        }

        $rule = $rule->update(
            ruleType: $input->ruleType !== null ? SafetyRuleType::from($input->ruleType) : null,
            ruleConfig: $input->ruleConfig,
            severity: $input->severity !== null ? RuleSeverity::from($input->severity) : null,
        );

        $this->ruleRepository->update($rule);

        return SafetyRuleOutput::fromEntity($rule);
    }
}
