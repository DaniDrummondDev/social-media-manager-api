<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class CreateAutomationRuleInput
{
    /**
     * @param  array<array<string, mixed>>  $conditions
     * @param  array<string>|null  $appliesToNetworks
     * @param  array<string>|null  $appliesToCampaigns
     */
    public function __construct(
        public string $organizationId,
        public string $name,
        public int $priority,
        public array $conditions,
        public string $actionType,
        public ?string $responseTemplate = null,
        public ?string $webhookId = null,
        public int $delaySeconds = 120,
        public int $dailyLimit = 100,
        public ?array $appliesToNetworks = null,
        public ?array $appliesToCampaigns = null,
    ) {}
}
