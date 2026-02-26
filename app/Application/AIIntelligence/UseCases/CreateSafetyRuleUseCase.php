<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\CreateSafetyRuleInput;
use App\Application\AIIntelligence\DTOs\SafetyRuleOutput;
use App\Domain\AIIntelligence\Entities\BrandSafetyRule;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyRuleType;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateSafetyRuleUseCase
{
    public function __construct(
        private readonly BrandSafetyRuleRepositoryInterface $ruleRepository,
    ) {}

    public function execute(CreateSafetyRuleInput $input): SafetyRuleOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $rule = BrandSafetyRule::create(
            organizationId: $organizationId,
            ruleType: SafetyRuleType::from($input->ruleType),
            ruleConfig: $input->ruleConfig,
            severity: RuleSeverity::from($input->severity),
        );

        $this->ruleRepository->create($rule);

        return SafetyRuleOutput::fromEntity($rule);
    }
}
