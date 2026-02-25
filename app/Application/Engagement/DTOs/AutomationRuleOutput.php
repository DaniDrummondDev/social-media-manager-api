<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\Entities\AutomationRule;

final readonly class AutomationRuleOutput
{
    /**
     * @param  array<array<string, mixed>>  $conditions
     * @param  array<string>|null  $appliesToNetworks
     * @param  array<string>|null  $appliesToCampaigns
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public int $priority,
        public array $conditions,
        public string $actionType,
        public ?string $responseTemplate,
        public ?string $webhookId,
        public int $delaySeconds,
        public int $dailyLimit,
        public bool $isActive,
        public ?array $appliesToNetworks,
        public ?array $appliesToCampaigns,
        public string $createdAt,
        public string $updatedAt,
        public ?int $executionsCount = null,
    ) {}

    public static function fromEntity(AutomationRule $rule, ?int $executionsCount = null): self
    {
        return new self(
            id: (string) $rule->id,
            organizationId: (string) $rule->organizationId,
            name: $rule->name,
            priority: $rule->priority,
            conditions: array_map(fn ($c) => [
                'field' => $c->field,
                'operator' => $c->operator->value,
                'value' => $c->value,
                'is_case_sensitive' => $c->isCaseSensitive,
                'position' => $c->position,
            ], $rule->conditions),
            actionType: $rule->actionType->value,
            responseTemplate: $rule->responseTemplate,
            webhookId: $rule->webhookId !== null ? (string) $rule->webhookId : null,
            delaySeconds: $rule->delaySeconds,
            dailyLimit: $rule->dailyLimit,
            isActive: $rule->isActive,
            appliesToNetworks: $rule->appliesToNetworks,
            appliesToCampaigns: $rule->appliesToCampaigns,
            createdAt: $rule->createdAt->format('c'),
            updatedAt: $rule->updatedAt->format('c'),
            executionsCount: $executionsCount,
        );
    }
}
