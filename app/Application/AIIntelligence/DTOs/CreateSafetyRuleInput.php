<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class CreateSafetyRuleInput
{
    /**
     * @param  array<string, mixed>  $ruleConfig
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $ruleType,
        public array $ruleConfig,
        public string $severity,
    ) {}
}
