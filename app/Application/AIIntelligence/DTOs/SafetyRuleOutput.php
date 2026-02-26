<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

use App\Domain\AIIntelligence\Entities\BrandSafetyRule;

final readonly class SafetyRuleOutput
{
    /**
     * @param  array<string, mixed>  $ruleConfig
     */
    public function __construct(
        public string $id,
        public string $ruleType,
        public array $ruleConfig,
        public string $severity,
        public bool $isActive,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(BrandSafetyRule $rule): self
    {
        return new self(
            id: (string) $rule->id,
            ruleType: $rule->ruleType->value,
            ruleConfig: $rule->ruleConfig,
            severity: $rule->severity->value,
            isActive: $rule->isActive,
            createdAt: $rule->createdAt->format('c'),
            updatedAt: $rule->updatedAt->format('c'),
        );
    }
}
