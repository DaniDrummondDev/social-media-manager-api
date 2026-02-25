<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class UpdateAutomationRuleInput
{
    /**
     * @param  array<array<string, mixed>>|null  $conditions
     * @param  array<string>|null  $appliesToNetworks
     * @param  array<string>|null  $appliesToCampaigns
     */
    public function __construct(
        public string $organizationId,
        public string $ruleId,
        public ?string $name = null,
        public ?int $priority = null,
        public ?array $conditions = null,
        public ?string $actionType = null,
        public ?string $responseTemplate = null,
        public ?string $webhookId = null,
        public ?int $delaySeconds = null,
        public ?int $dailyLimit = null,
        public ?array $appliesToNetworks = null,
        public ?array $appliesToCampaigns = null,
    ) {}
}
