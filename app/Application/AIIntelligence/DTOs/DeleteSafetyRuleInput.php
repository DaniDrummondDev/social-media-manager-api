<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class DeleteSafetyRuleInput
{
    public function __construct(
        public string $organizationId,
        public string $ruleId,
    ) {}
}
