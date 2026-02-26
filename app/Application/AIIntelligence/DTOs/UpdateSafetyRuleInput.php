<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class UpdateSafetyRuleInput
{
    /**
     * @param  array<string, mixed>|null  $ruleConfig
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $ruleId,
        public ?string $ruleType = null,
        public ?array $ruleConfig = null,
        public ?string $severity = null,
    ) {}
}
